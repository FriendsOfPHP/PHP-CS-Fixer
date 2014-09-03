<?php

/*
 * This file is part of the Symfony CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\PSR2;

use Symfony\CS\AbstractFixer;

/**
 * Fixer for rules defined in PSR2 ¶4.3, ¶4.3, ¶4.4, ¶5.
 *
 * @author Marek Kalnik <marekk@theodo.fr>
 */
class CurlyBracketsNewlineFixer extends AbstractFixer
{
    const REMOVE_NEWLINE = '\\1 {\\4';

    // Capture the indentation first
    const ADD_NEWLINE = "\\1\\2\n\\1{";

    /**
     * {@inheritdoc}
     */
    public function fix(\SplFileInfo $file, $content)
    {
        $content = $this->functionDeclarationFix($content);
        $content = $this->anonymousFunctionsFix($content);
        $content = $this->controlStatementsFix($content);

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(\SplFileInfo $file)
    {
        return 'php' === pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Opening braces for classes, interfaces, traits and methods must go on the next line, and closing braces must go on the next line after the body. Opening braces for control structures must go on the same line, and closing braces must go on the next line after the body.';
    }

    private function functionDeclarationFix($content)
    {
        // [Structure] Add new line after function declaration
        return preg_replace('/^([ \t]*)((?:[\w \t]+ )?function [\w \t]+\(.*?\))[ \t]*{\s*$/m', self::ADD_NEWLINE, $content);
    }

    private function anonymousFunctionsFix($content)
    {
        // [Structure] No new line after anonymous function call
        return preg_replace('/((^|[\s\W])function\s*\(.*\))([^\n]*?) *\n[^\S\n]*{/', self::REMOVE_NEWLINE, $content);
    }

    private function controlStatementsFix($content)
    {
        $statements = array(
            '\bif\s*\(.*\)',
            '\belse\s*if\s*\(.*\)',
            '\b(?<!\$)else\b',
            '\bfor\s*\(.*\)',
            '\b(?<!\$)do\b',
            '\bwhile\s*\(.*\)',
            '\bforeach\s*\(.*\)',
            '\bswitch\s*\(.*\)',
            '\b(?<!\$)try\b',
            '\bcatch\s*\(.*\)',
        );

        // [Structure] No new line after control statements
        return preg_replace('/((^|[\s\W])('.implode('|', $statements).'))([^\n]*?) *\n[^\S\n]*{/', self::REMOVE_NEWLINE, $content);
    }
}
