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

namespace PhpCsFixer\Tests\Fixer\FunctionNotation;

use PhpCsFixer\Test\AbstractFixerTestCase;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer
 */
final class FunctionTypehintSpaceFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases()
    {
        return array(
            array(
                '<?php function foo($param) {}',
            ),
            array(
                '<?php function foo($param1,$param2) {}',
            ),
            array(
                '<?php function foo(&$param) {}',
            ),
            array(
                '<?php function foo(& $param) {}',
            ),
            array(
                '<?php function foo(/**int*/$param) {}',
            ),
            array(
                '<?php function foo(callable $param) {}',
                '<?php function foo(callable$param) {}',
            ),
            array(
                '<?php function foo(array &$param) {}',
                '<?php function foo(array&$param) {}',
            ),
            array(
                '<?php function foo(array & $param) {}',
                '<?php function foo(array& $param) {}',
            ),
            array(
                '<?php function foo(Bar $param) {}',
                '<?php function foo(Bar$param) {}',
            ),
            array(
                '<?php function foo(Bar\Baz $param) {}',
                '<?php function foo(Bar\Baz$param) {}',
            ),
            array(
                '<?php function foo(Bar\Baz &$param) {}',
                '<?php function foo(Bar\Baz&$param) {}',
            ),
            array(
                '<?php function foo(Bar\Baz & $param) {}',
                '<?php function foo(Bar\Baz& $param) {}',
            ),
            array(
                '<?php $foo = function(Bar\Baz $param) {};',
                '<?php $foo = function(Bar\Baz$param) {};',
            ),
            array(
                '<?php $foo = function(Bar\Baz &$param) {};',
                '<?php $foo = function(Bar\Baz&$param) {};',
            ),
            array(
                '<?php $foo = function(Bar\Baz & $param) {};',
                '<?php $foo = function(Bar\Baz& $param) {};',
            ),
            array(
                '<?php class Test { public function foo(Bar\Baz $param) {} }',
                '<?php class Test { public function foo(Bar\Baz$param) {} }',
            ),
            array(
                '<?php $foo = function(array $a,
                    array $b, array     $c, array
                    $d) {};',
                '<?php $foo = function(array $a,
                    array$b, array     $c, array
                    $d) {};',
            ),
        );
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideCases56
     */
    public function testFix56($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideCases56()
    {
        return array(
            array(
                '<?php function foo(...$param) {}',
            ),
            array(
                '<?php function foo(&...$param) {}',
            ),
            array(
                '<?php function foo(array ...$param) {}',
                '<?php function foo(array...$param) {}',
            ),
            array(
                '<?php function foo(array & ...$param) {}',
                '<?php function foo(array& ...$param) {}',
            ),
        );
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provide70Cases
     * @requires PHP 7.0
     */
    public function test70($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provide70Cases()
    {
        return array(
            array('<?php use function some\test\{fn_a, fn_b, fn_c};'),
            array('<?php use function some\test\{fn_a, fn_b, fn_c} ?>'),
        );
    }
}
