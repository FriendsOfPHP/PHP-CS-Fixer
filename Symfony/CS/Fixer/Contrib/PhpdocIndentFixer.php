<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Contrib;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Ceeram <ceeram@cakephp.org>
 */
class PhpdocIndentFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        foreach ($tokens->findGivenKind(T_DOC_COMMENT) as $index => $token) {
            $next = $tokens->getNextMeaningfulToken($index);
            if ($next === null) {
                continue;
            }

            $indent = $this->calculateIndent($tokens[$next - 1]->getContent());

            $prevToken = $tokens[$index - 1];

            $prevToken->setContent($this->fixWhitespaceBefore($prevToken->getContent(), $indent));

            $token->setContent($this->fixDocBlock($token->getContent(), $indent));
        }

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Docblocks should have the same indentation as the documented subject.';
    }

    /**
     * Fix indentation of Docblock.
     *
     * @param string $content Docblock contents
     * @param string $indent  Indentation to apply
     *
     * @return string Dockblock contents including correct indentation
     */
    private function fixDocBlock($content, $indent)
    {
        return ltrim(preg_replace('/^[ \t]*/m', $indent.' ', $content));
    }

    /**
     * Fix whitespace before the Docblock.
     *
     * @param string $content Whitespace before Docblock
     * @param string $indent  Indentation of the documented subject
     *
     * @return string Whitespace including correct indentation for Dockblock after this whitespace
     */
    private function fixWhitespaceBefore($content, $indent)
    {
        return rtrim($content, ' ').$indent;
    }

    /**
     * Calculate used indentation from the whitespace before documented subject.
     *
     * @param string $content Whitespace before documented subject
     *
     * @return string
     */
    private function calculateIndent($content)
    {
        return ltrim(strrchr(str_replace(array("\r\n", "\r"), "\n", $content), 10), "\n");
    }
}
