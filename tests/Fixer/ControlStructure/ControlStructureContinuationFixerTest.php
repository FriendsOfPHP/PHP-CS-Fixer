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

namespace PhpCsFixer\Tests\Fixer\ControlStructure;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\ControlStructure\ControlStructureContinuationFixer
 */
final class ControlStructureContinuationFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null, array $configuration = null)
    {
        if (null !== $configuration) {
            $this->fixer->configure($configuration);
        }

        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        yield 'else (same line, default)' => [
            '<?php
                if ($foo) {
                    foo();
                } else {
                    bar();
                }',
            '<?php
                if ($foo) {
                    foo();
                }
                else {
                    bar();
                }',
        ];

        yield 'elseif (same line, default)' => [
            '<?php
                if ($foo) {
                    foo();
                } elseif ($bar) {
                    bar();
                }',
            '<?php
                if ($foo) {
                    foo();
                }
                elseif ($bar) {
                    bar();
                }',
        ];

        yield 'else if (same line, default)' => [
            '<?php
                if ($foo) {
                    foo();
                } else if ($bar) {
                    bar();
                }',
            '<?php
                if ($foo) {
                    foo();
                }
                else if ($bar) {
                    bar();
                }',
        ];

        yield 'do while (same line, default)' => [
            '<?php
                do {
                    foo();
                } while ($foo);',
            '<?php
                do {
                    foo();
                }
                while ($foo);',
        ];

        yield 'try catch finally (same line, default)' => [
            '<?php
                try {
                    foo();
                } catch (Throwable $e) {
                    bar();
                } finally {
                    baz();
                }',
            '<?php
                try {
                    foo();
                }
                catch (Throwable $e) {
                    bar();
                }
                finally {
                    baz();
                }',
        ];

        yield 'else (next line)' => [
            '<?php
                if ($foo) {
                    foo();
                }
                else {
                    bar();
                }',
            '<?php
                if ($foo) {
                    foo();
                } else {
                    bar();
                }',
            ['keyword_position' => 'next_line'],
        ];

        yield 'elseif (next line)' => [
            '<?php
                if ($foo) {
                    foo();
                }
                elseif ($bar) {
                    bar();
                }',
            '<?php
                if ($foo) {
                    foo();
                } elseif ($bar) {
                    bar();
                }',
            ['keyword_position' => 'next_line'],
        ];

        yield 'else if (next line)' => [
            '<?php
                if ($foo) {
                    foo();
                }
                else if ($bar) {
                    bar();
                }',
            '<?php
                if ($foo) {
                    foo();
                } else if ($bar) {
                    bar();
                }',
            ['keyword_position' => 'next_line'],
        ];

        yield 'do while (next line)' => [
            '<?php
                do {
                    foo();
                }
                while ($foo);',
            '<?php
                do {
                    foo();
                } while ($foo);',
            ['keyword_position' => 'next_line'],
        ];

        yield 'try catch finally (next line)' => [
            '<?php
                try {
                    foo();
                }
                catch (Throwable $e) {
                    bar();
                }
                finally {
                    baz();
                }',
            '<?php
                try {
                    foo();
                } catch (Throwable $e) {
                    bar();
                } finally {
                    baz();
                }',
            ['keyword_position' => 'next_line'],
        ];

        yield 'else with comment after closing brace' => [
            '<?php
                if ($foo) {
                    foo();
                } // comment
                else {
                    bar();
                }',
        ];

        yield 'elseif with comment after closing brace' => [
            '<?php
                if ($foo) {
                    foo();
                } // comment
                elseif ($bar) {
                    bar();
                }',
        ];

        yield 'else if with comment after closing brace' => [
            '<?php
                if ($foo) {
                    foo();
                } // comment
                else if ($bar) {
                    bar();
                }',
        ];

        yield 'do while with comment after closing brace' => [
            '<?php
                do {
                    foo();
                } // comment
                while (false);',
        ];

        yield 'try catch finally with comment after closing brace' => [
            '<?php
                try {
                    foo();
                } // comment
                catch (Throwable $e) {
                    bar();
                } // comment
                finally {
                    baz();
                }',
        ];
    }
}
