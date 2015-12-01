<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS;

use Symfony\CS\Tokenizer\Tokens;

/**
 * @author Carlos Cirello <carlos.cirello.nl@gmail.com>
 */
abstract class AbstractAlignFixer extends AbstractFixer
{
    /**
     * @const Placeholder used as anchor for right alignment.
     */
    const ALIGNABLE_PLACEHOLDER = "\x2 ALIGNABLE%d \x3";

    /**
     * Look for group of placeholders, and provide vertical alignment.
     *
     * @param Tokens $tokens
     * @param int    $deepestLevel
     *
     * @return string
     */
    protected function replacePlaceholder(Tokens $tokens, $deepestLevel)
    {
        $tmpCode = $tokens->generateCode();

        for ($j = 0; $j <= $deepestLevel; ++$j) {
            $placeholder = sprintf(self::ALIGNABLE_PLACEHOLDER, $j);

            if (false === strpos($tmpCode, $placeholder)) {
                continue;
            }

            $lines = explode("\n", $tmpCode);
            $linesWithPlaceholder = array();
            $blockSize = 0;

            $linesWithPlaceholder[$blockSize] = array();

            foreach ($lines as $index => $line) {
                if (substr_count($line, $placeholder) > 0) {
                    $linesWithPlaceholder[$blockSize][] = $index;
                } else {
                    ++$blockSize;
                    $linesWithPlaceholder[$blockSize] = array();
                }
            }

            foreach ($linesWithPlaceholder as $group) {
                if (count($group) < 1) {
                    continue;
                }

                $rightmostSymbol = 0;
                foreach ($group as $index) {
                    $line = $this->cleanInnerSpaces($lines[$index], $placeholder);

                    $rightmostSymbol = max($rightmostSymbol, strpos(utf8_decode($line), $placeholder));
                }

                foreach ($group as $index) {
                    $line = $lines[$index];
                    $currentSymbol = strpos(utf8_decode($line), $placeholder);
                    $delta = $rightmostSymbol - $currentSymbol;

                    if ($delta > 0) {
                        $line = str_replace($placeholder, str_repeat(' ', $delta).$placeholder, $line);
                        $lines[$index] = $line;
                    } elseif ($delta < 0) {
                        $line = $this->cleanInnerSpaces($line, $placeholder);
                        $lines[$index] = $line;
                    }
                }
            }

            $tmpCode = str_replace($placeholder, '', implode("\n", $lines));
        }

        return $tmpCode;
    }

    /**
     * Cleans up extra spaces between variable and placeholder.
     *
     * Ex: `$ccc  = 1` becomes `$ccc = 1`.
     *
     * @param string $line
     * @param string $placeholder
     *
     * @return string
     */
    private function cleanInnerSpaces($line, $placeholder)
    {
        $lineBlocks = explode($placeholder, $line);
        $lineBlocks[0] = rtrim($lineBlocks[0]).' ';

        return implode($placeholder, $lineBlocks);
    }
}
