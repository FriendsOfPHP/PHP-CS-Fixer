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

namespace PhpCsFixer\Fixer\Phpdoc;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\Line;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Analyzer\ArgumentsAnalyzer;
use PhpCsFixer\Tokenizer\Analyzer\CommentsAnalyzer;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class PhpdocAddMissingParamAnnotationFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'PHPDoc should contain `@param` for all params.',
            [
                new CodeSample(
                    '<?php
/**
 * @param int $bar
 *
 * @return void
 */
function f9(string $foo, $bar, $baz) {}
'
                ),
                new CodeSample(
                    '<?php
/**
 * @param int $bar
 *
 * @return void
 */
function f9(string $foo, $bar, $baz) {}
',
                    ['only_untyped' => true]
                ),
                new CodeSample(
                    '<?php
/**
 * @param int $bar
 *
 * @return void
 */
function f9(string $foo, $bar, $baz) {}
',
                    ['only_untyped' => false]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before NoEmptyPhpdocFixer, NoSuperfluousPhpdocTagsFixer, PhpdocAlignFixer, PhpdocAlignFixer, PhpdocOrderFixer.
     * Must run after AlignMultilineCommentFixer, CommentToPhpdocFixer, GeneralPhpdocTagRenameFixer, PhpdocIndentFixer, PhpdocNoAliasTagFixer, PhpdocScalarFixer, PhpdocToCommentFixer, PhpdocTypesFixer.
     */
    public function getPriority()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAllTokenKindsFound([T_DOC_COMMENT, T_FUNCTION]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $argumentsAnalyzer = new ArgumentsAnalyzer();
        $commentsAnalyzer = new CommentsAnalyzer();

        for ($index = 0, $limit = $tokens->count(); $index < $limit; ++$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_DOC_COMMENT)) {
                continue;
            }

            $tokenContent = $token->getContent();

            if (false !== stripos($tokenContent, 'inheritdoc')) {
                continue;
            }

            // Optionally, ignore one-line PhpDoc's like `/** foo */`, as there is no place to put new annotations
            if ($this->configuration['skip_inline_phpdocs'] && false === strpos($tokenContent, "\n")) {
                continue;
            }

            if (
                $commentsAnalyzer->isHeaderComment($tokens, $index)
                || !$commentsAnalyzer->isBeforeStructuralElement($tokens, $index)
            ) {
                continue;
            }

            $mainIndex = $index;
            // Use instead of `getNextMeaningfulToken` to keep previous DocBlock
            $index = $tokens->getNextNonWhitespace($index);

            if (null === $index) {
                return;
            }

            // Step back to check comment at next iteration
            if ($tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
                --$index;

                continue;
            }

            while ($tokens[$index]->isGivenKind([
                T_ABSTRACT,
                T_FINAL,
                T_PRIVATE,
                T_PROTECTED,
                T_PUBLIC,
                T_STATIC,
                T_RETURN,
            ])) {
                $index = $tokens->getNextMeaningfulToken($index);
            }

            $tokenKinds = [T_FUNCTION];
            // To fix PhpDoc of closures/arrow functions in most used case: `return function(int $x){...};`
            if (\PHP_VERSION_ID >= 70400) {
                $tokenKinds[] = T_FN;
            }
            if (!$tokens[$index]->isGivenKind($tokenKinds)) {
                continue;
            }

            $openIndex = $tokens->getNextTokenOfKind($index, ['(']);
            $index = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $openIndex);

            $arguments = [];

            foreach ($argumentsAnalyzer->getArguments($tokens, $openIndex, $index) as $start => $end) {
                $argumentInfo = $this->prepareArgumentInformation($tokens, $start, $end);

                if (!$this->configuration['only_untyped'] || '' === $argumentInfo['type']) {
                    $arguments[$argumentInfo['name']] = $argumentInfo;
                }
            }

            if (!\count($arguments)) {
                continue;
            }

            $doc = new DocBlock($tokenContent);
            $lastParamLine = null;

            foreach ($doc->getAnnotationsOfType('param') as $annotation) {
                $pregMatched = Preg::match('/^[^$]+(\$\w+).*$/s', $annotation->getContent(), $matches);

                if (1 === $pregMatched) {
                    unset($arguments[$matches[1]]);
                }

                $lastParamLine = max($lastParamLine, $annotation->getEnd());
            }

            if (!\count($arguments)) {
                continue;
            }

            $lines = $doc->getLines();
            $linesCount = \count($lines);

            Preg::match('/^(\s*).*$/', $lines[$linesCount - 1]->getContent(), $matches);
            $indent = $matches[1];

            $newLines = [];

            foreach ($arguments as $argument) {
                $type = $argument['type'] ?: 'mixed';

                if ('mixed' !== $type && ('?' === $type[0] || 'null' === strtolower($argument['default']))) {
                    $type = 'null|'.ltrim($type, '?');
                }

                $newLines[] = new Line(sprintf(
                    '%s* @param %s %s%s',
                    $indent,
                    $type,
                    $argument['name'],
                    $this->whitespacesConfig->getLineEnding()
                ));
            }

            array_splice(
                $lines,
                $lastParamLine ? $lastParamLine + 1 : $linesCount - 1,
                0,
                $newLines
            );

            $tokens[$mainIndex] = new Token([T_DOC_COMMENT, implode('', $lines)]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('only_untyped', 'Whether to add missing `@param` annotations for untyped parameters only.'))
                ->setDefault(true)
                ->setAllowedTypes(['bool'])
                ->getOption(),
            (new FixerOptionBuilder('skip_inline_phpdocs', 'Ignore one-line PhpDocs like as `/** foo */`.'))
                ->setDefault(true)
                ->setAllowedTypes(['bool'])
                ->getOption(),
        ]);
    }

    /**
     * @param int $start
     * @param int $end
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

            if ($token->isComment() || $token->isWhitespace()) {
                continue;
            }

            if ($token->isGivenKind(T_VARIABLE)) {
                $sawName = true;
                $info['name'] = $token->getContent();

                continue;
            }

            if ($token->equals('=')) {
                continue;
            }

            if ($sawName) {
                $info['default'] .= $token->getContent();
            } elseif ('&' !== $token->getContent()) {
                if ($token->isGivenKind(T_ELLIPSIS)) {
                    if ('' === $info['type']) {
                        $info['type'] = 'array';
                    } else {
                        $info['type'] .= '[]';
                    }
                } else {
                    $info['type'] .= $token->getContent();
                }
            }
        }

        return $info;
    }
}
