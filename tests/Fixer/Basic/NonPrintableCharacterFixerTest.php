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

namespace PhpCsFixer\Tests\Fixer\Basic;

use PhpCsFixer\Test\AbstractFixerTestCase;

/**
 * @author Ivan Boprzenkov <ivan.borzenkov@gmail.com>
 *
 * @internal
 */
final class NonPrintableCharacterFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFixCases()
    {
        return array(
            array(
                '<?php echo "Hello World !";',
            ),
            array(
                '<?php echo "Hello World !";',
                '<?php echo "'.pack('CCC', 0xe2, 0x80, 0x8b).'Hello'.pack('CCC', 0xe2, 0x80, 0x87).'World'.pack('CC', 0xc2, 0xa0).'!";',
            ),
            array(
                '<?php
// echo
echo "Hello World !";',
                '<?php
// ec'.pack('CCC', 0xe2, 0x80, 0x8b).'ho
echo "Hello World !";',
            ),
        );
    }
}
