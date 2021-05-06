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

namespace PhpCsFixer\Fixer\Basic;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Tokens;

final class NoMultipleStatementsPerLineFixer extends AbstractFixer implements WhitespacesAwareFixerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'There must not be more than one statement per line.',
            [new CodeSample("<?php\nfoo(); bar();\n")]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(';');
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens)
    {
        for ($index = 1, $max = \count($tokens) - 1; $index < $max; ++$index) {
            if ($tokens[$index]->isGivenKind(T_FOR)) {
                $index = $tokens->findBlockEnd(
                    Tokens::BLOCK_TYPE_PARENTHESIS_BRACE,
                    $tokens->getNextTokenOfKind($index, ['('])
                );

                continue;
            }

            if (!$tokens[$index]->equals(';')) {
                continue;
            }

            for ($nextIndex = $index + 1; $nextIndex < $max; ++$nextIndex) {
                $token = $tokens[$nextIndex];

                if ($token->isWhitespace() || $token->isComment()) {
                    if (1 === Preg::match('/\R/', $token->getContent())) {
                        break;
                    }

                    continue;
                }

                if (!$token->equalsAny(['}', [T_CLOSE_TAG], [T_ENDIF], [T_ENDFOR], [T_ENDSWITCH], [T_ENDWHILE], [T_ENDFOREACH]])) {
                    $whitespaceIndex = $index;
                    do {
                        $token = $tokens[++$whitespaceIndex];
                    } while ($token->isComment());

                    $newline = $this->whitespacesConfig->getLineEnding().$this->getLineIndentation($tokens, $index);

                    if ($tokens->ensureWhitespaceAtIndex($whitespaceIndex, 0, $newline)) {
                        ++$max;
                    }
                }

                break;
            }
        }
    }

    private function getLineIndentation(Tokens $tokens, $index)
    {
        $newlineTokenIndex = $this->getPreviousNewlineTokenIndex($tokens, $index);

        if (null === $newlineTokenIndex) {
            return '';
        }

        return $this->extractIndent($this->computeNewLineContent($tokens, $newlineTokenIndex));
    }

    private function extractIndent($content)
    {
        if (Preg::match('/\R([\t ]*)[^\r\n]*$/D', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function getPreviousNewlineTokenIndex(Tokens $tokens, $index)
    {
        while ($index > 0) {
            $index = $tokens->getPrevTokenOfKind($index, [[T_WHITESPACE], [T_INLINE_HTML]]);

            if (null === $index) {
                break;
            }

            if ($this->isNewLineToken($tokens, $index)) {
                return $index;
            }
        }

        return null;
    }

    private function isNewLineToken(Tokens $tokens, $index)
    {
        if (!$tokens[$index]->isGivenKind(T_WHITESPACE)) {
            return false;
        }

        return (bool) Preg::match('/\R/', $this->computeNewLineContent($tokens, $index));
    }

    private function computeNewLineContent(Tokens $tokens, $index)
    {
        $content = $tokens[$index]->getContent();

        if (0 !== $index && $tokens[$index - 1]->equalsAny([[T_OPEN_TAG], [T_CLOSE_TAG]])) {
            $content = Preg::replace('/\S/', '', $tokens[$index - 1]->getContent()).$content;
        }

        return $content;
    }
}
