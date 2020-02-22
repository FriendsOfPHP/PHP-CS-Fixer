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

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @author Varga Bence <vbence@czentral.org>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer
 */
final class NoSpacesAfterFunctionNameFixerTest extends AbstractFixerTestCase
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
            'test function call' => [
                '<?php abc($a);',
                '<?php abc ($a);',
            ],
            'test method call' => [
                '<?php $o->abc($a);',
                '<?php $o->abc ($a);',
            ],
            'test function-like constructs' => [
                '<?php
    unset($hello);
    isset($hello);
    empty($hello);
    die($hello);
    array("hello");
    list($a, $b) = $c;
    eval("a");
    foo();
    $foo = &ref();
    ',
                '<?php
    unset ($hello);
    isset ($hello);
    empty ($hello);
    die ($hello);
    array ("hello");
    list ($a, $b) = $c;
    eval ("a");
    foo ();
    $foo = &ref ();
    ',
            ],
            'don\'t touch non-function-like constructs' => [
                '<?php
    include ("something.php");
    include_once ("something.php");
    require ("something.php");
    require_once ("something.php");
    print ("hello");
    echo ("hello");
    ',
            ],
            [
                '<?php echo foo(1) ? "y" : "n";',
                '<?php echo foo (1) ? "y" : "n";',
            ],
            [
                '<?php echo isset($name) ? "y" : "n";',
                '<?php echo isset ($name) ? "y" : "n";',
            ],
            [
                '<?php include (isHtml())? "1.html": "1.php";',
                '<?php include (isHtml ())? "1.html": "1.php";',
            ],
            // skip other language constructs
            [
                '<?php $a = 2 * (1 + 1);',
            ],
            [
                '<?php echo ($a == $b) ? "foo" : "bar";',
            ],
            [
                '<?php echo ($a == test($b)) ? "foo" : "bar";',
            ],
            [
                '<?php include ($html)? "custom.html": "custom.php";',
            ],
            'don\'t touch echo expressions' => [
                '<?php
    echo ($a ?: $b) . $c;
    echo (2 + 3) * 4, "\n";
    ',
            ],
            'don\'t touch function declarations' => [
                '<?php
                function TisMy ($p1)
                {
                    print $p1;
                }
                ',
            ],
            [
                '<?php class A {
                    function TisMy    ($p1)
                    {
                        print $p1;
                    }
                }',
            ],
            'test dynamic by array' => [
                '<?php $a["e"](1); $a{2}(1);',
                '<?php $a["e"] (1); $a{2} (1);',
            ],
            'test variable variable' => [
                '<?php
${$e}(1);
$$e(2);
                ',
                "<?php
\${\$e}\t(1);
\$\$e    (2);
                ",
            ],
            'test dynamic function and method calls' => [
                '<?php $b->$a(); $c();',
                '<?php $b->$a  (); $c  ();',
            ],
            'test function call comment' => [
                '<?php abc#
 ($a);',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFix54Cases
     */
    public function test54($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix54Cases()
    {
        return [
            [
                '<?php echo (new Process())->getOutput();',
                '<?php echo (new Process())->getOutput ();',
            ],
        ];
    }

    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFix70Cases
     * @requires PHP 7.0
     */
    public function test70($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix70Cases()
    {
        return [
            [
                '<?php $a()(1);',
                '<?php $a () (1);',
            ],
        ];
    }
}
