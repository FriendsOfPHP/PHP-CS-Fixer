<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Symfony;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Token;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class FunctionTypehintSpaceFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        for ($index = $tokens->count() - 1; $index >= 0; --$index) {
            $token = $tokens[$index];

            if (!$token->isGivenKind(T_FUNCTION)) {
                continue;
            }

            $startParenthesisIndex = $tokens->getNextTokenOfKind($index, array('('));
            $endParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $startParenthesisIndex);

            for ($iter = $endParenthesisIndex - 1; $iter > $startParenthesisIndex; --$iter) {
                if (!$tokens[$iter]->isGivenKind(T_VARIABLE)) {
                    continue;
                }

                $prevNonWhitespaceIndex = $tokens->getPrevNonWhitespace($iter);
                $pos = $tokens[$prevNonWhitespaceIndex]->equals('&')
                    ? $prevNonWhitespaceIndex
                    : $iter;

                if (!$tokens[$pos - 1]->equalsAny(array(array(T_WHITESPACE), array(T_COMMENT), array(T_DOC_COMMENT), '('))) {
                    $tokens->insertAt($pos, new Token(array(T_WHITESPACE, ' ', $tokens[$pos]->getLine())));
                }
            }
        }

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Add missing space between function\'s argument and its typehint.';
    }
}
