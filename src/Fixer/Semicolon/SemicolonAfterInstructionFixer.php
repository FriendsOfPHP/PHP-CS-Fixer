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

namespace PhpCsFixer\Fixer\Semicolon;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;

/**
 * @author SpacePossum
 */
final class SemicolonAfterInstructionFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Instructions must be terminated with a semicolon.',
            [new CodeSample("<?php echo 1 ?>\n")]
        );
    }

    /**
     * {@inheritdoc}
     *
     * Must run before SimplifiedIfReturnFixer.
     */
    public function getPriority(): int
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isTokenKindFound(T_CLOSE_TAG);
    }

    /**
     * {@inheritdoc}
     */
    protected function applyFix(\SplFileInfo $file, Tokens $tokens): void
    {
        for ($index = \count($tokens) - 1; $index > 1; --$index) {
            if (!$tokens[$index]->isGivenKind(T_CLOSE_TAG)) {
                continue;
            }

            $prev = $tokens->getPrevMeaningfulToken($index);
            if ($tokens[$prev]->equalsAny([';', '{', '}', ':', [T_OPEN_TAG]])) {
                continue;
            }

            $tokens->insertAt($prev + 1, new Token(';'));
        }
    }
}
