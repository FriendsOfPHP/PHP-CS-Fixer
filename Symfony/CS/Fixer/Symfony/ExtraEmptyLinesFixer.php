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
use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class ExtraEmptyLinesFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $tokens = Tokens::fromCode($content);

        foreach ($tokens as $token) {
            if (!$token->isGivenKind(T_WHITESPACE)) {
                continue;
            }

            $content = '';
            $count = 0;
            $parts = explode("\n", $token->getContent());

            for ($i = 0, $last = count($parts) - 1; $i <= $last; ++$i) {
                if ('' === $parts[$i]) {
                    // if part is empty then we between two \n
                    ++$count;
                } else {
                    $count = 0;
                    $content .= $parts[$i];
                }

                if ($i !== $last && 3 > $count) {
                    $content .= "\n";
                }
            }

            $token->setContent($content);
        }

        return $tokens->generateCode();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Removes extra empty lines.';
    }
}
