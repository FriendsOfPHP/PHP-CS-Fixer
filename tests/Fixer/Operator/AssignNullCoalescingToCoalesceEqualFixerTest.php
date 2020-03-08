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

namespace PhpCsFixer\Tests\Fixer\Operator;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author SpacePossum
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Operator\AssignNullCoalescingToCoalesceEqualFixer
 */
final class AssignNullCoalescingToCoalesceEqualFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFix74Cases
     * @requires PHP 7.4
     */
    public function testFix74($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix74Cases()
    {
        yield 'simple' => [
            '<?php $a ??= 1;',
            '<?php $a = $a ?? 1;',
        ];

        yield 'minimal' => [
            '<?php $a ??= 1;',
            '<?php $a=$a??1;',
        ];

        yield 'simple array' => [
            '<?php $a[1] ??= 1;',
            '<?php $a[1] = $a[1] ?? 1;',
        ];

        yield 'simple array, comment' => [
            '<?php $a[1] /* 1 */ ??= /* 2 */ /* 3 */ /* 4 */ /* 5 */ 1;',
            '<?php $a[1]/* 1 */ = /* 2 */ $a[1/* 3 */] /* 4 */ ??/* 5 */ 1;',
        ];

        yield 'mixed array' => [
            '<?php
                $a[1] ??= 1;
                $a{2} ??= 1;
                $a{2}[$f] ??= 1;
            ',
            '<?php
                $a[1] = $a[1] ?? 1;
                $a{2} = $a{2} ?? 1;
                $a{2}[$f] = $a{2}[$f] ?? 1;
            ',
        ];

        yield [
            '<?php \A\B::$foo ??= 1;',
            '<?php \A\B::$foo = \A\B::$foo ?? 1;',
        ];

        yield 'same' => [
            '<?php $a ??= 1;',
            '<?php $a = ($a) ?? 1;',
        ];

        yield 'same II' => [
            '<?php $a[1] ??= 1;',
            '<?php $a[1] = $a{1} ?? 1;',
        ];

        yield 'same III' => [
            '<?php $a[1] ??= 1;',
            '<?php $a[1] = (($a{1})) ?? 1;',
        ];

        yield 'object' => [
            '<?php $a->b ??= 1;',
            '<?php $a->b = $a->b ?? 1;',
        ];

        yield 'object II' => [
            '<?php $a->b[0]->{1} ??= 1;',
            '<?php $a->b[0]->{1} = $a->b[0]->{1} ?? 1;',
        ];

        yield 'simple, before ;' => [
            '<?php ; $a ??= 1;',
            '<?php ; $a = $a ?? 1;',
        ];

        yield 'simple, before {' => [
            '<?php { $a ??= 1; }',
            '<?php { $a = $a ?? 1; }',
        ];

        yield 'simple, before }' => [
            '<?php if ($a){} $a ??= 1;',
            '<?php if ($a){} $a = $a ?? 1;',
        ];

        yield 'simple, before (' => [
            '<?php foo( $a ??= 1 );',
            '<?php foo( $a = $a ?? 1 );',
        ];

        yield 'simple, before ) I' => [
            '<?php if ($a) $a ??= 1;',
            '<?php if ($a) $a = $a ?? 1;',
        ];

        yield 'simple, before ) II' => [
            '<?php
                if ($a) $a ??= 1;
                foreach ($d as $i) $a ??= 1;
                while (foo()) $a ??= 1;
            ',
            '<?php
                if ($a) $a = $a ?? 1;
                foreach ($d as $i) $a = $a ?? 1;
                while (foo()) $a = $a ?? 1;
            ',
        ];

        yield 'simple, end' => [
            '<?php $a ??= 1 ?>',
            '<?php $a = $a ?? 1 ?>',
        ];

        yield 'simple, 10x' => [
            '<?php'.str_repeat(' $a ??= 1;', 10),
            '<?php'.str_repeat(' $a = $a ?? 1;', 10),
        ];

        yield 'simple, multi line' => [
            '<?php
            $a
             ??=
              '.'
               '.'
                1;',
            '<?php
            $a
             =
              $a
               ??
                1;',
        ];

        yield 'do not fix' => [
            '<?php
                $a = 1 + $a ?? $b;
                $b + $a = $a ?? 1;
                $b = $a ?? 1;
                $b = $a ?? $b;
                $d = $a + $c ; $c ?? $c;
                $a = ($a ?? $b) && $c; // just to be sure
            ',
        ];
    }
}
