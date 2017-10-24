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
 * @requires PHP 7.1
 *
 * @author Jack Cherng <jfcherng@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Fixer\Whitespace\CompactNullableTypehintFixer
 */
final class CompactNullableTypehintFixerTest extends AbstractFixerTestCase
{
    /**
     * @param string      $expected
     * @param null|string $input
     *
     * @dataProvider provideFix71Cases
     * @requires PHP 7.1
     */
    public function testFix71($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    public function provideFix71Cases()
    {
        return [
            [
                '<?php function foo(?int $param): ?int {}',
            ],
            [
                '<?php function foo(? /* foo */ int $param): ? /* foo */ int {}',
            ],
            [
                '<?php function foo(? /** foo */ int $param): ? /** foo */ int {}',
            ],
            [
                '<?php function foo(? // foo
                    int $param): ? // foo
                    int {}',
            ],
            [
                '<?php function foo(/**? int*/$param): ?int {}',
                '<?php function foo(/**? int*/$param): ? int {}',
            ],
            [
                '<?php function foo(?callable $param): ?callable {}',
                '<?php function foo(? callable $param): ? callable {}',
            ],
            [
                '<?php function foo(?array &$param): ?array {}',
                '<?php function foo(? array &$param): ? array {}',
            ],
            [
                '<?php function foo(?Bar $param): ?Bar {}',
                '<?php function foo(? Bar $param): ? Bar {}',
            ],
            [
                '<?php function foo(?\Bar $param): ?\Bar {}',
                '<?php function foo(? \Bar $param): ? \Bar {}',
            ],
            [
                '<?php function foo(?Bar\Baz $param): ?Bar\Baz {}',
                '<?php function foo(? Bar\Baz $param): ? Bar\Baz {}',
            ],
            [
                '<?php function foo(?Bar\Baz &$param): ?Bar\Baz {}',
                '<?php function foo(? Bar\Baz &$param): ? Bar\Baz {}',
            ],
            [
                '<?php $foo = function(?Bar\Baz $param): ?Bar\Baz {};',
                '<?php $foo = function(? Bar\Baz $param): ? Bar\Baz {};',
            ],
            [
                '<?php $foo = function(?Bar\Baz &$param): ?Bar\Baz {};',
                '<?php $foo = function(? Bar\Baz &$param): ? Bar\Baz {};',
            ],
            [
                '<?php class Test { public function foo(?Bar\Baz $param): ?Bar\Baz {} }',
                '<?php class Test { public function foo(? Bar\Baz $param): ? Bar\Baz {} }',
            ],
            [
                '<?php abstract class Test { abstract public function foo(?Bar\Baz $param); }',
                '<?php abstract class Test { abstract public function foo(? Bar\Baz $param); }',
            ],
            [
                '<?php $foo = function(?array $a,
                    ?array $b): ?Bar\Baz {};',
                '<?php $foo = function(?
                    array $a,
                    ? array $b): ?
                    Bar\Baz {};',
            ],
            [
                '<?php function foo(?array ...$param): ?array {}',
                '<?php function foo(? array ...$param): ? array {}',
            ],
        ];
    }
}
