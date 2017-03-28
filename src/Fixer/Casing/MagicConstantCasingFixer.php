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

namespace PhpCsFixer\Fixer\Casing;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\Tokenizer\CT;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @author ntzm
 */
final class MagicConstantCasingFixer extends AbstractFixer
{
    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        $magicConstants = $this->getMagicConstants();
        $magicConstantTokens = $this->getMagicConstantTokens();

        foreach ($tokens as $token) {
            if ($token->isGivenKind($magicConstantTokens)) {
                $token->setContent($magicConstants[$token->getId()]);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefinition()
    {
        return new FixerDefinition(
            'Magic constants should be referred to using the correct casing.',
            array(new CodeSample("<?php\necho __dir__;"))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isCandidate(Tokens $tokens)
    {
        return $tokens->isAnyTokenKindsFound($this->getMagicConstantTokens());
    }

    /**
     * @return array<int, string>
     */
    private function getMagicConstants()
    {
        static $magicConstants = null;

        if (null === $magicConstants) {
            $magicConstants = array(
                T_LINE => '__LINE__',
                T_FILE => '__FILE__',
                T_DIR => '__DIR__',
                T_FUNC_C => '__FUNCTION__',
                T_CLASS_C => '__CLASS__',
                T_METHOD_C => '__METHOD__',
                T_NS_C => '__NAMESPACE__',
            );

            if (defined('T_TRAIT_C')) {
                $magicConstants[T_TRAIT_C] = '__TRAIT__';
            }

            if (PHP_VERSION_ID >= 50500) {
                $magicConstants[CT::T_CLASS_CONSTANT] = 'class';
            }
        }

        return $magicConstants;
    }

    /**
     * @return array<int>
     */
    private function getMagicConstantTokens()
    {
        static $magicConstantTokens = null;

        if (null === $magicConstantTokens) {
            $magicConstantTokens = array_keys($this->getMagicConstants());
        }

        return $magicConstantTokens;
    }
}
