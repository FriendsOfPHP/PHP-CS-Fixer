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

namespace PhpCsFixer\Fixer\PhpTag;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\VersionSpecification;
use PhpCsFixer\FixerDefinition\VersionSpecificCodeSample;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author Vincent Klaiber <hello@vinkla.com>
 */
final class OpeningTagPlusEchoToShortEchoTagFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        $i = count($tokens);
        $lastEcho = null;

        while ($i--) {
            $token = $tokens[$i];

            if ($token->isWhitespace()) {
                continue;
            } elseif ($token->equals(array(T_ECHO, 'echo'))) {
                $lastEcho = $i;
                continue;
            } elseif (!$token->isGivenKind(T_OPEN_TAG)) {
                $lastEcho = null;
                continue;
            }

            if ($lastEcho !== null) {
                $tokens->overrideAt($i, array(T_OPEN_TAG_WITH_ECHO, '<?='));
                for ($j = $i + 1; $j <= $lastEcho; ++$j) {
                    $tokens[$j]->clear();
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Replace `<?php echo` with short-echo `<?=` syntax.',
            array(new VersionSpecificCodeSample('<?php echo "foo";', new VersionSpecification(50400)))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isTokenKindFound(T_OPEN_TAG);
    }
}
