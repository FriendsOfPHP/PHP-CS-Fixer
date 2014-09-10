<?php

/*
 * This file is part of the Symfony CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Symfony;

use Symfony\CS\AbstractFixer;
use Symfony\CS\Token;
use Symfony\CS\Tokens;

/**
 * @author Bram Gotink <bram@gotink.me>
 */
class NamespaceNoLeadingWhitespaceFixer extends AbstractFixer
{
    private static function endsWithWhitespace($str)
    {
        return strlen($str) > 0 && ctype_space(substr($str, -1));
    }

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        foreach ($tokens as $index => $token) {
            if (!$token->isGivenKind(T_NAMESPACE)) {
                continue;
            }

            $beforeNamespace = $tokens[$index - 1];

            if (!$beforeNamespace->isWhitespace()) {
                if (!self::endsWithWhitespace($beforeNamespace->content)) {
                    $beforeNamespace->content .= "\n";
                }

                continue;
            }

            $lastNewline = strrpos($beforeNamespace->content, "\n");

            if (false === $lastNewline) {
                $beforeBeforeNamespace = $tokens[$index - 2];

                if (self::endsWithWhitespace($beforeBeforeNamespace->content)) {
                    $beforeNamespace->content = '';
                } else {
                    $beforeNamespace->content = ' ';
                }
            } else {
                $beforeNamespace->content = substr($beforeNamespace->content, 0, $lastNewline + 1);
            }
        }

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'The namespace declaration line shouldn\'t contain leading whitespace.';
    }
}
