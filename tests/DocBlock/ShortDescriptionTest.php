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

namespace PhpCsFixer\Tests\DocBlock;

use PhpCsFixer\DocBlock\DocBlock;
use PhpCsFixer\DocBlock\ShortDescription;
use PhpCsFixer\Tests\TestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\DocBlock\ShortDescription
 */
final class ShortDescriptionTest extends TestCase
{
    /**
     * @param null|mixed $input
     * @param mixed      $expected
     *
     * @dataProvider provideGetEndCases
     */
    public function testGetEnd($expected, $input = null)
    {
        $doc = new DocBlock($input);
        $shortDescription = new ShortDescription($doc);

        $this->assertSame($expected, $shortDescription->getEnd());
    }

    public function provideGetEndCases()
    {
        return [
            [null, '/** */'],
            [1, '/**
     * Test docblock.
     *
     * @param string $hello
     * @param bool $test Description
     *        extends over many lines
     *
     * @param adkjbadjasbdand $asdnjkasd
     *
     * @throws \Exception asdnjkasd
     * asdasdasdasdasdasdasdasd
     * kasdkasdkbasdasdasdjhbasdhbasjdbjasbdjhb
     *
     * @return void
     */'],
            [2, '/**
                  * This is a multi-line
                  * short description.
                  */'],
            [3, '/**
                  *
                  *
                  * There might be extra blank lines.
                  *
                  */'],
        ];
    }
}
