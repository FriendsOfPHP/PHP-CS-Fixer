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

namespace PhpCsFixer\Tests\Linter;

use PhpCsFixer\Linter\ProcessLinterProcessBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Linter\ProcessLinterProcessBuilder
 */
final class ProcessLinterProcessBuilderTest extends TestCase
{
    /**
     * @param string $executable
     * @param string $file
     * @param string $expected
     *
     * @testWith ["php", "foo.php", "'php' '-l' 'foo.php'"]
     *           ["C:\\Program Files\\php\\php.exe", "foo bar\\baz.php", "'C:\\Program Files\\php\\php.exe' '-l' 'foo bar\\baz.php'"]
     * @requires OS Linux|Darwin
     */
    public function testPrepareCommandOnPhpOnLinuxOrMac($executable, $file, $expected)
    {
        $this->assertSame(
            $expected,
            (new ProcessLinterProcessBuilder($executable))->build($file)->getCommandLine()
        );
    }

    /**
     * @param string $executable
     * @param string $file
     * @param string $expected
     *
     * @testWith ["php", "foo.php", "php -l foo.php"]
     *           ["C:\\Program Files\\php\\php.exe", "foo bar\\baz.php", "\"C:\\Program Files\\php\\php.exe\" -l \"foo bar\\baz.php\""]
     * @requires OS ^Win
     */
    public function testPrepareCommandOnPhpOnWindows($executable, $file, $expected)
    {
        $this->assertSame(
            $expected,
            (new ProcessLinterProcessBuilder($executable))->build($file)->getCommandLine()
        );
    }

    public function testPrepareCommandOnHhvm()
    {
        if (!defined('HHVM_VERSION')) {
            $this->markTestSkipped('Skip tests for HHVM compiler when running on PHP compiler.');
        }

        $this->assertSame(
            "'hhvm' '--php' '-l' 'foo.php'",
            (new ProcessLinterProcessBuilder('hhvm'))->build('foo.php')->getCommandLine()
        );
    }
}
