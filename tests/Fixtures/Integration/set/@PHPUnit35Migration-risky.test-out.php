<?php

class FooTest extends \PHPUnit_Framework_TestCase {
    public function test_dedicate_assert($foo) {
        $this->assertNull($foo);
        $this->assertInternalType('array', $foo);
        $this->assertTrue(is_nan($foo));
        $this->assertTrue(is_readable($foo));
    }
}
