<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Strict;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author VeeWee <toonverwerft@gmail.com>
 */
final class StrictMethodsFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Force strict types in class methods. Requires PHP >= 7.0.',
            [
                new VersionSpecificCodeSample(
                    '<?php ',
                    new VersionSpecification(70000)
                ),
            ],
            null,
            ''
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        // must ran before ?????.
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return PHP_VERSION_ID >= 70000 && $tokens->isTokenKindFound(T_DOC_COMMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function isRisky()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $lastIndex = $tokens->count() - 1;

        for ($index = $lastIndex; $index >= 0; --$index) {
            if (!$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $this->useAnnotatedTypesAsStrictTypes($tokens, $index);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $docBlockIndex
     */
    private function useAnnotatedTypesAsStrictTypes(Tokens $tokens, $docBlockIndex)
    {
        $doc = new DocBlock($tokens[$docBlockIndex]->getContent());
        if (!count($doc->getAnnotationsOfType(['param', 'return']))) {
            return;
        }

        $functionTokenIndex = $this->detectNextFunctionToken($tokens, $docBlockIndex);
        if (null === $functionTokenIndex) {
            return;
        }

        $arguments = $this->detectFunctionArguments($tokens, $functionTokenIndex);

        foreach ($arguments as $variable => $argument) {
            foreach ($doc->getAnnotationsOfType('param') as $annotation) {
                if (!preg_match('/'.preg_quote($variable, '/').'\b/', $annotation->getContent())) {
                    continue;
                }

                $types = $annotation->getTypes();
                $typesCount = count($types);
                if (1 === $typesCount && $types[0] === $variable) {
                    $annotation->remove();

                    continue;
                }

                if (1 !== $typesCount || 'mixed' === $types[0]) {
                    continue;
                }

                if (!$argument['type']) {
                    $this->fixMethodArguments($tokens, $argument['name_index'], $types[0]);
                    $argument['type'] = $types[0];
                }

                if ($types[0] === $argument['type']) {
                    $annotation->remove();
                }
            }
        }

        if (null === $this->detectFunctionReturnType($tokens, $functionTokenIndex)) {
            $annotations = $doc->getAnnotationsOfType('return');
            if (1 === count($annotations)) {
                $types = $annotations[0]->getTypes();
                if (1 === count($types)) {
                    $this->fixMethodReturnType($tokens, $functionTokenIndex, current($types));
                    $annotations[0]->remove();
                }
            }
        }

        // Remove empty dockblocks
        if (preg_match('/\s*\/\*\*\s*\*\/\s*/', $doc->getContent())) {
            $tokens->clearAt($docBlockIndex);

            return;
        }

        $tokens[$docBlockIndex] = new Token([T_DOC_COMMENT, $doc->getContent()]);
    }

    private function fixMethodArguments(Tokens $tokens, int $argumentNameIndex, string $type)
    {
        $tokens->insertAt($argumentNameIndex, [
            new Token([T_STRING, $type]),
            new Token([T_WHITESPACE, ' ']),
        ]);
    }

    private function fixMethodReturnType(Tokens $tokens, int $methodIndex, string $type)
    {
        $argumentsStart = $tokens->getNextTokenOfKind($methodIndex, ['(']);
        $argumentsEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $argumentsStart);

        $nextTokenIndex = $tokens->getNextMeaningfulToken($argumentsEnd);
        if ($tokens[$nextTokenIndex]->equals(':')) {
            return;
        }

        $tokens->insertAt($argumentsEnd + 1, [
            new Token([CT::T_TYPE_COLON, ':']),
            new Token([T_WHITESPACE, ' ']),
            new Token([T_STRING, $type]),
        ]);
    }

    /**
     * @param Tokens $tokens
     * @param int    $docBlockIndex
     *
     * @return null|int
     */
    private function detectNextFunctionToken(Tokens $tokens, $docBlockIndex)
    {
        $allowedIntermediateToken = [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_FINAL, T_ABSTRACT, T_STATIC];
        $currentIndex = $docBlockIndex;
        do {
            $currentIndex = $tokens->getNextMeaningfulToken($currentIndex);
            $token = $tokens[$currentIndex];

            if ($token->isGivenKind(T_FUNCTION)) {
                return $currentIndex;
            }

            if (!$token->isGivenKind($allowedIntermediateToken)) {
                return null;
            }
        } while ($currentIndex < count($tokens));

        return null;
    }

    /**
     * @param Tokens $tokens
     * @param int    $methodIndex
     *
     * @return array
     */
    private function detectFunctionArguments(Tokens $tokens, $methodIndex)
    {
        $argumentsStart = $tokens->getNextTokenOfKind($methodIndex, ['(']);
        $argumentsEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $argumentsStart);
        $argumentAnalyzer = new ArgumentsAnalyzer();
        $arguments = [];

        foreach ($argumentAnalyzer->getArguments($tokens, $argumentsStart, $argumentsEnd) as $start => $end) {
            $argumentInfo = $this->prepareArgumentInformation($tokens, $start, $end);
            $arguments[$argumentInfo['name']] = $argumentInfo;
        }

        if (!count($arguments)) {
            return [];
        }

        return $arguments;
    }

    /**
     * @param Tokens $tokens
     * @param int    $methodIndex
     *
     * @return null|int
     */
    private function detectFunctionReturnType(Tokens $tokens, $methodIndex)
    {
        $argumentsStart = $tokens->getNextTokenOfKind($methodIndex, ['(']);
        $argumentsEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $argumentsStart);

        $colonIndex = $tokens->getNextMeaningfulToken($argumentsEnd);
        if (!$tokens[$colonIndex]->isGivenKind([CT::T_TYPE_COLON])) {
            return null;
        }

        return $tokens->getNextMeaningfulToken($colonIndex);
    }

    /**
     * TODO: This method is copied from \PhpCsFixer\Fixer\Phpdoc\PhpdocAddMissingParamAnnotationFixer
     * I've added some small improvements like skipping ellipsis and adding the name_index
     * We might abstract here?
     *
     *
     * @param Tokens $tokens
     * @param int    $start
     * @param int    $end
     *
     * @return array
     */
    private function prepareArgumentInformation(Tokens $tokens, $start, $end)
    {
        $info = [
            'default' => '',
            'name' => '',
            'type' => '',
        ];

        $sawName = false;

        for ($index = $start; $index <= $end; ++$index) {
            $token = $tokens[$index];

            if ($token->isComment() || $token->isWhitespace() || $token->isGivenKind(T_ELLIPSIS)) {
                continue;
            }

            if ($token->isGivenKind(T_VARIABLE)) {
                $sawName = true;
                $info['name_index'] = $index;
                $info['name'] = $token->getContent();

                continue;
            }

            if ($token->equals('=')) {
                continue;
            }

            if ($sawName) {
                $info['default'] .= $token->getContent();
            } else {
                $info['type'] .= $token->getContent();
            }
        }

        return $info;
    }
}
