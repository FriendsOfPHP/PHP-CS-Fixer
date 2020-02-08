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

namespace PhpCsFixer\Fixer\ControlStructure;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurationDefinitionFixerInterface;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\Tokens;

final class ControlStructureContinuationFixer extends AbstractFixer implements ConfigurationDefinitionFixerInterface, WhitespacesAwareFixerInterface
{
    /**
     * @internal
     */
    const NEXT_LINE = 'next_line';

    /**
     * @internal
     */
    const SAME_LINE = 'same_line';

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Control structure continuation keyword must be on the configured line.',
            [
                new CodeSample(
                    '<?php
if ($baz == true) {
    echo "foo";
}
else {
    echo "bar";
}
'
                ),
                new CodeSample(
                    '<?php
if ($baz == true) {
    echo "foo";
} else {
    echo "bar";
}
',
                    ['keyword_position' => self::NEXT_LINE]
                ),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * Must run after ControlStructureBracesFixer.
     */
    public function getPriority()
    {
        return parent::getPriority();
    }

    /**
     * {@inheritdoc}
     */
    protected function createConfigurationDefinition()
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('keyword_position', 'the position of the keyword that continues the control structure.'))
                ->setAllowedValues([self::NEXT_LINE, self::SAME_LINE])
                ->setDefault(self::SAME_LINE)
                ->getOption(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        $this->fixControlContinuationBraces($tokens);
        $this->fixDoWhile($tokens);
    }

    private function fixControlContinuationBraces(Tokens $tokens)
    {
        $controlContinuationTokens = $this->getControlContinuationTokens();

        for ($index = \count($tokens) - 1; 0 <= $index; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind($controlContinuationTokens)) {
                continue;
            }

            $prevIndex = $tokens->getPrevNonWhitespace($index);
            $prevToken = $tokens[$prevIndex];

            if (!$prevToken->equals('}')) {
                continue;
            }

            $tokens->ensureWhitespaceAtIndex(
                $index - 1,
                1,
                self::NEXT_LINE === $this->configuration['keyword_position'] ?
                    $this->whitespacesConfig->getLineEnding().$this->detectIndent($tokens, $index)
                    : ' '
            );
        }
    }

    /**
     * @param int $index
     *
     * @return string
     */
    private function detectIndent(Tokens $tokens, $index)
    {
        while (true) {
            $whitespaceIndex = $tokens->getPrevTokenOfKind($index, [[T_WHITESPACE]]);

            if (null === $whitespaceIndex) {
                return '';
            }

            $whitespaceToken = $tokens[$whitespaceIndex];

            if (false !== strpos($whitespaceToken->getContent(), "\n")) {
                break;
            }

            $prevToken = $tokens[$whitespaceIndex - 1];

            if ($prevToken->isGivenKind([T_OPEN_TAG, T_COMMENT]) && "\n" === substr($prevToken->getContent(), -1)) {
                break;
            }

            $index = $whitespaceIndex;
        }

        $explodedContent = explode("\n", $whitespaceToken->getContent());

        return end($explodedContent);
    }

    private function fixDoWhile(Tokens $tokens)
    {
        for ($index = \count($tokens) - 1; 0 <= $index; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_DO)) {
                continue;
            }

            $parenthesisEndIndex = $this->findParenthesisEnd($tokens, $index);
            $startBraceIndex = $tokens->getNextNonWhitespace($parenthesisEndIndex);
            $endBraceIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_CURLY_BRACE, $startBraceIndex);
            $nextNonWhitespaceIndex = $tokens->getNextNonWhitespace($endBraceIndex);
            $nextNonWhitespaceToken = $tokens[$nextNonWhitespaceIndex];

            if (!$nextNonWhitespaceToken->isGivenKind(T_WHILE)) {
                continue;
            }

            $tokens->ensureWhitespaceAtIndex(
                $nextNonWhitespaceIndex - 1,
                1,
                self::NEXT_LINE === $this->configuration['keyword_position'] ?
                    $this->whitespacesConfig->getLineEnding().$this->detectIndent($tokens, $index)
                    : ' '
            );
        }
    }

    /**
     * @param int $structureTokenIndex
     *
     * @return int
     */
    private function findParenthesisEnd(Tokens $tokens, $structureTokenIndex)
    {
        $nextIndex = $tokens->getNextMeaningfulToken($structureTokenIndex);
        $nextToken = $tokens[$nextIndex];

        // return if next token is not opening parenthesis
        if (!$nextToken->equals('(')) {
            return $structureTokenIndex;
        }

        return $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $nextIndex);
    }

    private function getControlContinuationTokens()
    {
        static $tokens = [
            T_CATCH,
            T_ELSE,
            T_ELSEIF,
            T_FINALLY,
        ];

        return $tokens;
    }
}
