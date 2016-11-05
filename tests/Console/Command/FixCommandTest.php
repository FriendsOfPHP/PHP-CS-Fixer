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

namespace PhpCsFixer\Tests\Console\Command;

use PhpCsFixer\Console\Command\FixCommand;
use PhpCsFixer\Test\AccessibleObject;

/**
 * @author Andreas Möller <am@localheinz.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class FixCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCommandHasCacheFileOption()
    {
        $command = new FixCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('cache-file'));

        $option = $definition->getOption('cache-file');

        $this->assertNull($option->getShortcut());
        $this->assertTrue($option->isValueRequired());
        $this->assertSame('The path to the cache file', $option->getDescription());
        $this->assertNull($option->getDefault());
    }

    /**
     * @dataProvider provideCalculateExitStatusCases
     */
    public function testCalculateExitStatus($expected, $isDryRun, $hasChangedFiles, $hasInvalidErrors, $hasExceptionErrors)
    {
        $command = new AccessibleObject(new FixCommand());

        $this->assertSame(
            $expected,
            $command->calculateExitStatus($isDryRun, $hasChangedFiles, $hasInvalidErrors, $hasExceptionErrors)
        );
    }

    public function provideCalculateExitStatusCases()
    {
        return array(
            array(0, true, false, false, false),
            array(0, false, false, false, false),
            array(8, true, true, false, false),
            array(0, false, true, false, false),
            array(4, true, false, true, false),
            array(0, false, false, true, false),
            array(12, true, true, true, false),
            array(0, false, true, true, false),
            array(76, true, true, true, true),
        );
    }
}
