<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\PSR2;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * Fixer for rules defined in PSR2 ¶4.6.
 *
 * @author Varga Bence <vbence@czentral.org>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class FunctionCallSpaceFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound($this->getFunctionyTokens());
    }

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        $functionyTokens = $this->getFunctionyTokens();
        $languageConstructionTokens = $this->getLanguageConstructionTokens();

        foreach ($tokens as $index => $token) {
            // looking for start brace
            if (!$token->equals('(')) {
                continue;
            }

            // last non-whitespace token
            $lastTokenIndex = $tokens->getPrevNonWhitespace($index);

            if (null === $lastTokenIndex) {
                continue;
            }

            // check for ternary operator
            $endParenthesisIndex = $tokens->findBlockEnd(Tokens::BLOCK_TYPE_PARENTHESIS_BRACE, $index);
            $nextNonWhiteSpace = $tokens->getNextMeaningfulToken($endParenthesisIndex);
            if (!empty($nextNonWhiteSpace) && $tokens[$nextNonWhiteSpace]->equals('?')) {
                if ($tokens[$lastTokenIndex]->isGivenKind($languageConstructionTokens)) {
                    continue;
                }
            }

            // check if it is a function call
            if ($tokens[$lastTokenIndex]->isGivenKind($functionyTokens)) {
                $this->fixFunctionCall($tokens, $index);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'When making a method or function call, there MUST NOT be a space between the method or function name and the opening parenthesis.';
    }

    /**
     * Fixes whitespaces around braces of a function(y) call.
     *
     * @param Tokens $tokens tokens to handle
     * @param int    $index  index of token
     */
    private function fixFunctionCall(Tokens $tokens, $index)
    {
        // remove space before opening brace
        if ($tokens[$index - 1]->isWhitespace()) {
            $tokens[$index - 1]->clear();
        }
    }

    /**
     * Gets the name of tokens which can work as function calls.
     *
     * @staticvar string[] $tokens Token names.
     *
     * @return string[] Token names.
     */
    private function getFunctionyTokens()
    {
        static $tokens = null;

        if (null === $tokens) {
            $tokens = array(
                T_ARRAY,
                T_ECHO,
                T_EMPTY,
                T_EVAL,
                T_EXIT,
                T_INCLUDE,
                T_INCLUDE_ONCE,
                T_ISSET,
                T_LIST,
                T_PRINT,
                T_REQUIRE,
                T_REQUIRE_ONCE,
                T_STRING,   // for real function calls
                T_UNSET,
            );
        }

        return $tokens;
    }

    /**
     * Gets the name of tokens that are actually language construction.
     *
     * @return int[]
     */
    private function getLanguageConstructionTokens()
    {
        static $languageConstructionTokens = array(
            T_ECHO,
            T_PRINT,
            T_INCLUDE,
            T_INCLUDE_ONCE,
            T_REQUIRE,
            T_REQUIRE_ONCE,
        );
      
        return $languageConstructionTokens;
    }
}
