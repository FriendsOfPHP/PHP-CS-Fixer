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

namespace PhpCsFixer\Tokenizer\Analyzer;

use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 */
final class AlternativeSyntaxAnalyzer
{
    const ALTERNATIVE_SYNTAX_BLOCK_EDGES = [
        T_IF => [T_ENDIF, T_ELSE, T_ELSEIF],
        T_ELSE => [T_ENDIF],
        T_ELSEIF => [T_ENDIF, T_ELSE, T_ELSEIF],
        T_FOR => [T_ENDFOR],
        T_FOREACH => [T_ENDFOREACH],
        T_WHILE => [T_ENDWHILE],
        T_SWITCH => [T_ENDSWITCH],
    ];

    /**
     * @param int $index
     *
     * @return int
     */
    public function findAlternativeSyntaxBlockEnd(Tokens $tokens, $index)
    {
        if (!isset($tokens[$index])) {
            throw new \InvalidArgumentException("There is no token at index {$index}.");
        }

        if (!$this->isStartOfAlternativeSyntaxBlock($tokens, $index)) {
            throw new \InvalidArgumentException("Token at index {$index} is not the start of an alternative syntax block.");
        }

        $startTokenKind = $tokens[$index]->getId();
        $endTokenKinds = self::ALTERNATIVE_SYNTAX_BLOCK_EDGES[$startTokenKind];

        $findKinds = [[$startTokenKind]];
        foreach ($endTokenKinds as $endTokenKind) {
            $findKinds[] = [$endTokenKind];
        }

        while (true) {
            $index = $tokens->getNextTokenOfKind($index, $findKinds);

            if ($tokens[$index]->isGivenKind($endTokenKinds)) {
                return $index;
            }

            $index = $this->findAlternativeSyntaxBlockEnd($tokens, $index);
        }
    }

    private function isStartOfAlternativeSyntaxBlock(Tokens $tokens, $index)
    {
        $map = self::ALTERNATIVE_SYNTAX_BLOCK_EDGES;
        $startTokenKind = $tokens[$index]->getId();

        if (null === $startTokenKind || !isset($map[$startTokenKind])) {
            return false;
        }

        $index = $tokens->getNextMeaningfulToken($index);

        if ($tokens[$index]->equals('(')) {
            $index = $tokens->getNextMeaningfulToken(
                $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index)
            );
        }

        return $tokens[$index]->equals(':');
    }
}
