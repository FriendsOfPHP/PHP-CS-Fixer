<?php

class Foo {
    /**
     * @access public
     */
    public $bar;

    /**
     * @type array $baz
     */


    public $baz;

/**
 *
 * Foo
 *
 * @throws Exception
 * @param inTeGer $fo This is int.
 *
 * @param float $bar This is float.
 * @param int $b Test phpdoc_param_order
 * @param bool $a Test phpdoc_param_order
 * @return void
 *
 *
 * @custom
 */
    public function foo ($a, $fo, $b, $bar, array $baz, $c, $qux) {}
}
