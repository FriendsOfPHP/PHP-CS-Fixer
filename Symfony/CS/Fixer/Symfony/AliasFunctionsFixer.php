<?php

/*
 * This file is part of the PHP CS utility.
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
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class AliasFunctionsFixer extends AbstractFixer
{
    /**
     * @var string[] stores alias (key) - master (value) functions mapping
     */
    private static $aliases = array(
        'chop' => 'rtrim',
        'close' => 'closedir',
        'doubleval' => 'floatval',
        'fputs' => 'fwrite',
        'join' => 'implode',
        'ini_alter' => 'ini_set',
        'is_double' => 'is_float',
        'is_integer' => 'is_int',
        'is_long' => 'is_int',
        'is_real' => 'is_float',
        'is_writeable' => 'is_writable',
        'key_exists' => 'array_key_exists',
        'magic_quotes_runtime' => 'set_magic_quotes_runtime',
        'pos' => 'current',
        'rewind' => 'rewinddir',
        'show_source' => 'highlight_file',
        'sizeof' => 'count',
        'strchr' => 'strstr',
    );

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, Tokens $tokens)
    {
        /** @var $token \Symfony\CS\Tokenizer\Token */
        foreach ($tokens->findGivenKind(T_STRING) as $index => $token) {
            // skip expressions without parameters list
            $nextToken = $tokens[$tokens->getNextMeaningfulToken($index)];
            if (!$nextToken->equals('(')) {
                continue;
            }

            // skip expressions which are not function reference
            $prevTokenIndex = $tokens->getPrevMeaningfulToken($index);
            $prevToken = $tokens[$prevTokenIndex];
            if ($prevToken->isGivenKind(array(T_DOUBLE_COLON, T_NEW, T_OBJECT_OPERATOR, T_FUNCTION))) {
                continue;
            }

            // handle function reference with namespaces
            if ($prevToken->isGivenKind(array(T_NS_SEPARATOR))) {
                $twicePrevTokenIndex = $tokens->getPrevMeaningfulToken($prevTokenIndex);
                $twicePrevToken = $tokens[$twicePrevTokenIndex];
                if ($twicePrevToken->isGivenKind(array(T_DOUBLE_COLON, T_NEW, T_OBJECT_OPERATOR, T_FUNCTION, T_STRING))) {
                    continue;
                }
            }

            // check mapping hit
            $tokenContent = strtolower($token->getContent());
            if (!isset(self::$aliases[$tokenContent])) {
                continue;
            }

            $token->setContent(self::$aliases[$tokenContent]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Master functions shall be used instead of aliases.';
    }
}
