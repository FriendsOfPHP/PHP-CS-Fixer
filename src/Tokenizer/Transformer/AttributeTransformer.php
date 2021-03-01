<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Tokenizer\Transformer;

use PhpCsFixer\Tokenizer\AbstractTransformer;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;

/**
 * Transforms attribute related Tokens.
 *
 * @internal
 */
final class AttributeTransformer extends AbstractTransformer
{
    /**
     * {@inheritdoc}
     */
    public function getPriority(): int
    {
        // must run before all other transformers that might touch attributes
        return 200;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredPhpVersionId(): int
    {
        return 80000;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Tokens $tokens, Token $token, int $index): void
    {
        if (!$tokens[$index]->isGivenKind(T_ATTRIBUTE)) {
            return;
        }

        $level = 1;

        do {
            ++$index;

            if ($tokens[$index]->equals('[')) {
                ++$level;
            } elseif ($tokens[$index]->equals(']')) {
                --$level;
            }
        } while (0 < $level);

        $tokens[$index] = new Token([CT::T_ATTRIBUTE_CLOSE, ']']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCustomTokens(): array
    {
        return [
            CT::T_ATTRIBUTE_CLOSE,
        ];
    }
}
