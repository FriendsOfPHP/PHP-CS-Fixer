<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tests\Fixer\Contrib;

use Symfony\CS\Tests\Fixer\AbstractFixerTestBase;

/**
 * @author Ceeram <ceeram@cakephp.org>
 */
class PhpdocIndentFixerTest extends AbstractFixerTestBase
{
    /**
     * @dataProvider provideDocblocks
     */
    public function testFixIndent($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provideDocblocks()
    {
        $cases = array();

        $cases[] = array(
            '
<?php
/**
 * Do not indent
 */

/**
 * Do not indent this
 */
class DocBlocks
{
    /**
     *Test that attribute docblocks are indented
     */
    protected $indent = false;

    /**
     * Test that method docblocks are indented.
     */
    public function test() {}
}',          '
<?php
/**
 * Do not indent
 */

/**
 * Do not indent this
 */
class DocBlocks
{
/**
 *Test that attribute docblocks are indented
 */
    protected $indent = false;

/**
 * Test that method docblocks are indented.
 */
    public function test() {}
}',
        );

        $cases[] = array(
            '
<?php
class DocBlocks
{
    /**
     * Test constants
     */
    const INDENT = 1;
    /**
     * Test without keywords
     */
    $without = false;

    /**
     * Test with var keyword
     */
    var $oldStyle = false;

    /**
     * Test static
     */
    public static function test() {}

    /**
     * Test static first.
     */
    static public function test() {}

    /**
     * Test final first.
     */
    final public function test() {}

    /**
     * Test no keywords
     */
    function test() {}
}',          '
<?php
class DocBlocks
{
/**
 * Test constants
 */
    const INDENT = 1;
/**
 * Test without keywords
 */
    $without = false;

/**
 * Test with var keyword
 */
    var $oldStyle = false;

/**
 * Test static
 */
    public static function test() {}

/**
 * Test static first.
 */
    static public function test() {}

/**
 * Test final first.
 */
    final public function test() {}

/**
 * Test no keywords
 */
    function test() {}
}',
        );

        $cases[] = array(
            '
<?php
/**
 * Final class should also not be indented
 */
final class DocBlocks
{
    /**
     * Test without keywords
     */
    $without = false;

    /**
     * Test with var keyword
     */
    var $oldStyle = false;
}',          '
<?php
/**
 * Final class should also not be indented
 */
final class DocBlocks
{
/**
 * Test without keywords
 */
    $without = false;

/**
 * Test with var keyword
 */
    var $oldStyle = false;
}',
        );

        $cases[] = array(
            '
<?php
    if (1) {
        class Foo {
            /**
             * Foo
             */
            function foo() {}

            /**
             * Bar
             */
            function bar() {}
        }
    }',          '
<?php
    if (1) {
        class Foo {
/**
 * Foo
 */
            function foo() {}

                        /**
                         * Bar
                         */
            function bar() {}
        }
    }',
        );

        $cases[] = array(
            '
<?php
/**
 * Variable
 */
$variable = true;

    /** Single line */
    $single = true;

    /**
     * Function
     */
    function something()
    {
        /**
         * Inside functions
         */
        return null;
    }

    /**
     * function call
     */
    something();

    /**
     * Control structure
     * @var \Sqlite3 $sqlite
     */
    foreach($connections as $sqlite) {
        $sqlite->open();
    }',          '
<?php
    /**
     * Variable
     */
$variable = true;

/** Single line */
    $single = true;

/**
 * Function
 */
    function something()
    {
/**
 * Inside functions
 */
        return null;
    }

/**
 * function call
 */
    something();

/**
 * Control structure
 * @var \Sqlite3 $sqlite
 */
    foreach($connections as $sqlite) {
        $sqlite->open();
    }',
        );

        return $cases;
    }
}
