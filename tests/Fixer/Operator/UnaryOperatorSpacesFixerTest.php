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
 * @author Gregor Harlan <gharlan@web.de>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Operator\UnaryOperatorSpacesFixer
 */
final class UnaryOperatorSpacesFixerTest extends AbstractFixerTestCase
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
        return [
            [
                '<?php $a= 1;$a#
++#
;#',
            ],
            [
                '<?php $a++;',
                '<?php $a ++;',
            ],
            [
                '<?php $a--;',
                '<?php $a --;',
            ],
            [
                '<?php ++$a;',
                '<?php ++ $a;',
            ],
            [
                '<?php --$a;',
                '<?php -- $a;',
            ],
            [
                '<?php $a = !$b;',
                '<?php $a = ! $b;',
            ],
            [
                '<?php $a = !!$b;',
                '<?php $a = ! ! $b;',
            ],
            [
                '<?php $a = ~$b;',
                '<?php $a = ~ $b;',
            ],
            [
                '<?php $a = &$b;',
                '<?php $a = & $b;',
            ],
            [
                '<?php $a=&$b;',
            ],
            [
                '<?php $a * -$b;',
                '<?php $a * - $b;',
            ],
            [
                '<?php $a *-$b;',
                '<?php $a *- $b;',
            ],
            [
                '<?php $a /-$b;',
                '<?php $a /- $b;',
            ],
            [
                '<?php $a ^-$b;',
                '<?php $a ^- $b;',
            ],
            [
                '<?php $a*-$b;',
            ],
            [
                '<?php function &foo(){}',
                '<?php function & foo(){}',
            ],
            [
                '<?php function &foo(){}',
                '<?php function &   foo(){}',
            ],
            [
                '<?php function foo(&$a, array &$b, Bar &$c) {}',
                '<?php function foo(& $a, array & $b, Bar & $c) {}',
            ],
            [
                '<?php function foo($a, ...$b) {}',
                '<?php function foo($a, ... $b) {}',
            ],
            [
                '<?php function foo(&...$a) {}',
                '<?php function foo(& ... $a) {}',
            ],
            [
                '<?php function foo(array ...$a) {}',
            ],
            [
                '<?php foo(...$a);',
                '<?php foo(... $a);',
            ],
            [
                '<?php foo($a, ...$b);',
                '<?php foo($a, ... $b);',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixWithSuccessorSpaceCases
     */
    public function testFixWithSuccessorSpace($expected, $input = null)
    {
        $this->fixer->configure(['not_operator_space' => 'one_trailing']);

        $this->doTest($expected, $input);
    }

    public function provideFixWithSuccessorSpaceCases()
    {
        return [
            [
                '<?php $i = 0; $i++; $foo = ! false || (! true || ! ! false && (2 === (7 -5)));',
                '<?php $i = 0; $i++; $foo = !false || (!true || !!false && (2 === (7 -5)));',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i && ! true);',
                '<?php $i = 0; $i--; $foo = !false || ($i && !true);',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i && ! /* some comment */true);',
                '<?php $i = 0; $i--; $foo = !false || ($i && !/* some comment */true);',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i && ! true);',
                '<?php $i = 0; $i--; $foo = !false || ($i && !    true);',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i && ! /* some comment */ true);',
                '<?php $i = 0; $i--; $foo = !false || ($i && !  /* some comment */ true);',
            ],
            'comment case' => [
                '<?php
                $a=#
! #
$b;
                ',
                '<?php
                $a=#
!
#
$b;
                ',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFixNotOperatorWithSpaceCases
     */
    public function testFixNotOperatorWithSpace($expected, $input = null)
    {
        $this->fixer->configure(['not_operator_space' => 'leading_and_trailing']);

        $this->doTest($expected, $input);
    }

    public function provideFixNotOperatorWithSpaceCases()
    {
        return [
            [
                '<?php $i = 0; $i++; ++$i; $foo = ! false || ( ! true);',
                '<?php $i = 0; $i++; ++$i; $foo = !false || (!true);',
            ],
            [
                '<?php $i = 0; $i--; --$i; $foo = ! false || ($i && ! true);',
                '<?php $i = 0; $i--; --$i; $foo = !false || ($i && !true);',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i && ! /* some comment */true);',
                '<?php $i = 0; $i--; $foo = !false || ($i && !/* some comment */true);',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i && !    true);',
                '<?php $i = 0; $i--; $foo = !false || ($i && !    true);',
            ],
            [
                '<?php $i = 0; $i--; $foo = ! false || ($i &&    !    true);',
                '<?php $i = 0; $i--; $foo = !false || ($i &&    !    true);',
            ],
        ];
    }
}
