<?php

/*
 * This file is part of the Symfony CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tests\Fixer\PSR2;

use Symfony\CS\Fixer\PSR2\StructureBracesFixer as Fixer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class StructureBracesFixerTest extends \PHPUnit_Framework_TestCase
{
    private function makeTest($expected, $input = null)
    {
        $fixer = new Fixer();
        $file = $this->getTestFile();

        if (null !== $input) {
            $this->assertSame($expected, $fixer->fix($file, $input));
        }

        $this->assertSame($expected, $fixer->fix($file, $expected));
    }

    /**
     * @dataProvider provideFixControlContinuationBracesCases
     */
    public function testFixControlContinuationBraces($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixControlContinuationBracesCases()
    {
        return array(
            array(
                '<?php
    if (true) {
        $a = 1;
    } else {
        $b = 2;
    }',
                '<?php
    if (true) {
        $a = 1;
    }
    else {
        $b = 2;
    }',
            ),
            array(
        '<?php
    try {
        throw new \Exeption();
    } catch (\LogicException $e) {
        // do nothing
    } catch (\Exception $e) {
        // do nothing
    }',
        '<?php
    try {
        throw new \Exeption();
    }catch (\LogicException $e) {
        // do nothing
    }
    catch (\Exception $e) {
        // do nothing
    }',
            ),
        );
    }

    /**
     * @dataProvider provideFixMissingBracesAndIndentCases
     */
    public function testFixMissingBracesAndIndent($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixMissingBracesAndIndentCases()
    {
        return array(
            array(
                '<?php
if (true):
    $foo = 0;
endif;',
            ),
array(
                '<?php
if (true)  :
    $foo = 0;
endif;',
            ),
            array(
                '<?php
    if (true) : $foo = 1; elseif;',
            ),
            array(
                '<?php
if (true) {
    $foo = 1;
}',
                '<?php
if (true)$foo = 1;',
            ),
            array(
                '<?php
if (true) {
    $foo = 2;
}',
                '<?php
if (true)    $foo = 2;',
            ),
            array(
                '<?php
if (true) {
    $foo = 3;
}',
                '<?php
if (true){$foo = 3;}',
            ),
            array(
                '<?php
if(true) {
    echo 1;
} else {
    echo 2;
}',
                '<?php
if(true) { echo 1; } else echo 2;',
            ),
            array(
                '<?php
if(true) {
    echo 3;
} else {
    echo 4;
}',
                '<?php
if(true) echo 3; else { echo 4; }',
            ),
            array(
                '<?php
if(true) {
    echo 5;
} else {
    echo 6;
}',
                '<?php
if(true) echo 5; else echo 6;',
            ),
            array(
                '<?php
if (true) {
    while (true) {
        $foo = 1;
        $bar = 2;
    }
}',
                '<?php
if (true) while (true) { $foo = 1; $bar = 2;}',
            ),
            array(
                '<?php
if (true) {
    if (true) {
        echo 1;
    } else {
        echo 2;
    }
} else {
    echo 3;
}',
                '<?php
if (true) if (true) echo 1; else echo 2; else echo 3;',
            ),
            array(
                '<?php
if (true) {
    // sth here...

    if ($a && ($b || $c)) {
        $d = 1;
    }
}',
                '<?php
if (true) {
    // sth here...

    if ($a && ($b || $c)) $d = 1;
}',
            ),
            array(
                '<?php
for ($i = 1; $i < 10; ++$) {
    echo $i;
}
for ($i = 1; $i < 10; ++$) {
    echo $i;
}',
                '<?php
for ($i = 1; $i < 10; ++$) echo $i;
for ($i = 1; $i < 10; ++$) { echo $i; }',
            ),
            array(
                '<?php
for ($i = 1; $i < 5; ++$i) {
    for ($i = 1; $i < 10; ++$i) {
        echo $i;
    }
}',
                '<?php
for ($i = 1; $i < 5; ++$i) for ($i = 1; $i < 10; ++$i) { echo $i; }',
            ),
            array(
                '<?php
do {
    echo 1;
} while (false);',
                '<?php
do { echo 1; } while (false);',
            ),
            array(
                '<?php
while($foo->next());',
            ),
            array(
                '<?php
foreach ($foo as $bar) {
    echo $bar;
}',
                '<?php
foreach ($foo as $bar) echo $bar;',
            ),
            array(
                '<?php
if (true) {
    $a = 1;
}',
                '<?php
if (true) {$a = 1;}',
            ),
            array(
                '<?php
if (true) {
    $a = 1;
}',
                '<?php
if (true) {
 $a = 1;
}',
            ),
            array(
                '<?php
if (true) {
    $a = 1;
    $b = 2;
    while (true) {
        $c = 3;
    }
    $d = 4;
}',
                '<?php
if (true) {
 $a = 1;
        $b = 2;
  while (true) {
            $c = 3;
                        }
        $d = 4;
}',
            ),
            array(
                '<?php
if (true) {
    $a = 1;


    $b = 2;
}',
            ),
            array(
                '<?php
if (1) {
    $a = 1;

    // comment at end
}',
            ),
            array(
                '<?php
if (1) {
    if (2) {
        $a = "a";
    } elseif (3) {
        $b = "b";
        // comment
    } else {
        $c = "c";
    }
    $d = "d";
}',
            ),
            array(
                '<?php
foreach ($numbers as $num) {
    for ($i = 0; $i < $num; ++$i) {
        $a = "a";
    }
    $b = "b";
}',
            ),
            array(
                '<?php
if (1) {
    if (2) {
        $foo = 2;

        if (3) {
            $foo = 3;
        }
    }
}',
            ),
            array(
                '<?php
    declare(ticks=1) {
        $ticks = 1;
    }',
                '<?php
    declare(ticks=1) {
  $ticks = 1;
    }',
            ),
            array(
                '<?php
    if (true) {
        foo();
    } elseif (true) {
        bar();
    }',
                '<?php
    if (true)
    {
        foo();
    } elseif (true)
    {
        bar();
    }',
            ),
            array(
                '<?php
    while (true) {
        foo();
    }',
                '<?php
    while (true)
    {
        foo();
    }',
            ),
            array(
                '<?php
    do {
        echo $test;
    } while ($test = $this->getTest());',
                '<?php
    do
    {
        echo $test;
    }
    while ($test = $this->getTest());',
            ),
            array(
                '<?php
    do {
        echo $test;
    } while ($test = $this->getTest());',
                '<?php
    do
    {
        echo $test;
    }while ($test = $this->getTest());',
            ),
            array(
                '<?php
    class ClassName
    {
        /**
         * comment
         */
        public $foo = null;
    }',
                '<?php
    class ClassName
    {




        /**
         * comment
         */
        public $foo = null;


    }',
            ),
            array(
                '<?php
    while ($true) {
        try {
            throw new \Exeption();
        } catch (\Exception $e) {
            // do nothing
        }
    }',
            ),
        );
    }

    /**
     * @dataProvider provideFixClassyBracesCases
     */
    public function testFixClassyBraces($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixClassyBracesCases()
    {
        return array(
            array(
                '<?php
                    class FooA
                    {
                    }',
                '<?php
                    class FooA {}',
            ),
            array(
                '<?php
                    class FooB
                    {
                    }',
                '<?php
                    class FooB{}',
            ),
            array(
                '<?php
                    class FooC
                    {
                    }',
                '<?php
                    class FooC
{}',
            ),
            array(
                '<?php
                    interface FooD
                    {
                    }',
                '<?php
                    interface FooD {}',
            ),
            array(
                '<?php
                class TestClass extends BaseTestClass implements TestInterface
                {
                    private $foo;
                }',
                '<?php
                class TestClass extends BaseTestClass implements TestInterface { private $foo;}',
            ),
            array(
                '<?php
<?php

abstract class Foo
{
    public function getProcess($foo)
    {
        return true;
    }
}',
            ),
        );
    }

    /**
     * @dataProvider provideFixClassyBraces54Cases
     * @requires PHP 5.4
     */
    public function testFixClassyBraces54($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideFixClassyBraces54Cases()
    {
        return array(
            array(
                '<?php
    trait TFoo
    {
        public $a;
    }',
                '<?php
    trait TFoo {public $a;}',
            ),
        );
    }

    private function getTestFile($filename = __FILE__)
    {
        static $files = array();

        if (!isset($files[$filename])) {
            $files[$filename] = new \SplFileInfo($filename);
        }

        return $files[$filename];
    }
}
