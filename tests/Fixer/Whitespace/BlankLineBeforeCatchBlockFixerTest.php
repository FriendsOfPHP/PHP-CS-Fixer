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

namespace PhpCsFixer\Tests\Fixer\Whitespace;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;

/**
 * @internal
 * @coversNothing
 */
final class BlankLineBeforeCatchBlockFixerTest extends AbstractFixerTestCase
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
                '<?php
try {
    foo();

} catch (\Exception $b) {
    bar();

} finally {
    baz();
}',
            ],
            [
                '<?php
try {
    foo();

} catch (\Exception $b) {
    bar();

} finally {
    baz();
}',
                '<?php
try {
    foo();
} catch (\Exception $b) {
    bar();
} finally {
    baz();
}',
            ],
            [
                '<?php
    try {
        foo();

    } catch (\Exception $b) {
        bar();

    } finally {
        baz();
    }',
                '<?php
    try {
        foo();
    } catch (\Exception $b) {
        bar();
    } finally {
        baz();
    }',
            ],
            [
                '<?php
try {
    foo();

/* Lorem ipsum */} catch (\Exception $b) {
    bar();

} finally {
    baz();
}',
                '<?php
try {
    foo();
/* Lorem ipsum */} catch (\Exception $b) {
    bar();
} finally {
    baz();
}',
            ],
            [
                '<?php try {foo();} catch (\Exception $b) {bar();} finally {baz();}',
            ],
        ];
    }
}
