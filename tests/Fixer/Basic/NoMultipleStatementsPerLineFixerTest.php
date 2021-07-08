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

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Basic\NoMultipleStatementsPerLineFixer
 */
final class NoMultipleStatementsPerLineFixerTest extends AbstractFixerTestCase
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
        yield 'simple' => [
            '<?php
                foo();
                bar();',
            '<?php
                foo(); bar();',
        ];

        yield 'for loop' => [
            '<?php
                for ($i = 0; $i < 10; ++$i) {
                    foo();
                }',
        ];

        yield 'followed by closing brace' => [
            '<?php if ($foo) { foo(); }',
        ];

        yield 'followed by closing tag' => [
            '<?php foo(); ?>',
        ];

        yield 'if alternative syntax' => [
            '<?php if ($foo): foo(); endif;',
        ];

        yield 'for alternative syntax' => [
            '<?php for (;;): foo(); endfor;',
        ];

        yield 'foreach alternative syntax' => [
            '<?php foreach ($foo as $bar): foo(); endforeach;',
        ];

        yield 'while alternative syntax' => [
            '<?php while ($foo): foo(); endwhile;',
        ];

        yield 'switch alternative syntax' => [
            '<?php switch ($foo): case true: foo(); endswitch;',
        ];
    }
}
