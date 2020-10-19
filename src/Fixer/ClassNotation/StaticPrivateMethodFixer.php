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

namespace PhpCsFixer\Fixer\ClassNotation;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Filippo Tessarotto <zoeslam@gmail.com>
 */
final class StaticPrivateMethodFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Converts private methods to `static` where possible.',
            [
                new CodeSample(
                    '<?php
class Foo
{
    public function bar()
    {
        return $this->baz();
    }

    private function baz()
    {
        return 1;
    }
}
'
                ),
            ],
            null,
            'Risky when method contains dynamic generated calls to the instance, or the method is dynamically referenced.'
        );
    }

    public function getPriority()
    {
        // Must run after ProtectedToPrivateFixer, before StaticLambdaFixer
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_CLASS, T_PRIVATE]);
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
        $end = \count($tokens) - 3; // min. number of tokens to form a class candidate to fix
        for ($index = 0; $index < $end; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_CLASS)) {
                continue;
            }

            $classOpen = $tokens->getNextTokenOfKind($index, ['{']);
            $classClose = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $classOpen);

            $this->fixClass($tokens, $classOpen, $classClose);

            $index = $classClose;
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $classOpen
     * @param int    $classClose
     */
    private function fixClass(Tokens $tokens, $classOpen, $classClose)
    {
        $methodsFound = [];
        $fixedMethods = [];
        for ($index = $classOpen + 1; $index < $classClose - 1; ++$index) {
            if (!$tokens[$index]->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $methodNameIndex = $tokens->getNextMeaningfulToken($index);
            $methodName = $tokens[$methodNameIndex]->getContent();
            $methodOpen = $tokens->getNextTokenOfKind($index, ['{']);
            $methodClose = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $methodOpen);
            $methodsFound[] = [
                'name' => $methodName,
                'curly_open' => $methodOpen,
                'curly_close' => $methodClose,
            ];

            $currentMethodIndex = $index;
            $index = $methodClose;

            if ($this->skipMethod($tokens, $currentMethodIndex, $methodOpen, $methodClose)) {
                continue;
            }

            $fixedMethods[$methodName] = true;

            $tokens->insertAt($currentMethodIndex, [new Token([T_STATIC, 'static']), new Token([T_WHITESPACE, ' '])]);
        }

        if (0 === \count($fixedMethods)) {
            return;
        }

        foreach ($methodsFound as $methodData) {
            $this->fixReferencesInFunction($tokens, $methodData['name'], $methodData['curly_open'], $methodData['curly_close'], $fixedMethods);
        }
    }

    /**
     * @param Tokens $tokens
     * @param int    $functionKeywordIndex
     * @param int    $methodOpen
     * @param int    $methodClose
     *
     * @return bool
     */
    private function skipMethod(Tokens $tokens, $functionKeywordIndex, $methodOpen, $methodClose)
    {
        $prevTokenIndex = $tokens->getPrevMeaningfulToken($functionKeywordIndex);
        if (!$tokens[$prevTokenIndex]->isGivenKind(T_PRIVATE)) {
            return true;
        }

        $prePrevTokenIndex = $tokens->getPrevMeaningfulToken($prevTokenIndex);
        if ($tokens[$prePrevTokenIndex]->isGivenKind(T_STATIC)) {
            return true;
        }

        for ($index = $methodOpen + 1; $index < $methodClose - 1; ++$index) {
            if ($tokens[$index]->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);

                continue;
            }

            if ($tokens[$index]->isGivenKind(T_FUNCTION)) {
                return true;
            }

            if ($tokens[$index]->equals([T_VARIABLE, '$this'])) {
                return true;
            }

            if ($tokens[$index]->equals([T_STRING, 'debug_backtrace'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Tokens      $tokens
     * @param null|string $name         Method name or null for Closures
     * @param int         $methodOpen
     * @param int         $methodClose
     * @param array       $fixedMethods
     */
    private function fixReferencesInFunction(Tokens $tokens, $name, $methodOpen, $methodClose, array $fixedMethods)
    {
        for ($index = $methodOpen + 1; $index < $methodClose - 1; ++$index) {
            if ($tokens[$index]->isGivenKind(T_FUNCTION)) {
                $prevIndex = $tokens->getPrevMeaningfulToken($index);
                $closureStart = $tokens->getNextTokenOfKind($index, ['{']);
                $closureEnd = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $closureStart);
                if (!$tokens[$prevIndex]->isGivenKind(T_STATIC)) {
                    $this->fixReferencesInFunction($tokens, null, $closureStart, $closureEnd, $fixedMethods);
                }

                $index = $closureEnd;

                continue;
            }
            if ($tokens[$index]->equals('{')) {
                $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $index);

                continue;
            }

            if (!$tokens[$index]->equals([T_VARIABLE, '$this'])) {
                continue;
            }

            $objectOperatorIndex = $tokens->getNextMeaningfulToken($index);
            if (!$tokens[$objectOperatorIndex]->isGivenKind(T_OBJECT_OPERATOR)) {
                continue;
            }

            $methodNameIndex = $tokens->getNextMeaningfulToken($objectOperatorIndex);
            $argumentsBraceIndex = $tokens->getNextMeaningfulToken($methodNameIndex);
            if (!$tokens[$argumentsBraceIndex]->equals('(')) {
                continue;
            }

            $currentMethodName = $tokens[$methodNameIndex]->getContent();
            if ($name === $currentMethodName) {
                continue;
            }

            if (!isset($fixedMethods[$currentMethodName])) {
                continue;
            }

            $tokens[$index] = new Token([T_STRING, 'self']);
            $tokens[$objectOperatorIndex] = new Token([T_DOUBLE_COLON, '::']);
        }
    }
}
