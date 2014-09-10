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
                return $content;
            }

            $lastNewline = strrpos($beforeNamespace->content, "\n");

            if (false === $lastNewline) {
                $beforeBeforeNamespace = $tokens[$index - 2];
                $last = substr($beforeBeforeNamespace->content, -1);

                if (ctype_space($last)) {
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
