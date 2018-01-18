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

namespace PhpCsFixer\Tests\Tokenizer\Analyzer;

use PhpCsFixer\Tokenizer\Analyzer\Analysis\NamespaceUseAnalysis;
use PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;
use PHPUnit\Framework\TestCase;

/**
 * @author VeeWee <toonverwerft@gmail.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Analyzer\NamespaceUsesAnalyzer
 */
final class NamespaceUsesAnalyzerTest extends TestCase
{
    /**
     * @param string $code
     * @param array  $expected
     *
     * @dataProvider provideNamespaceUsesCases
     */
    public function testUsesFromTokens($code, $expected)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new NamespaceUsesAnalyzer();

        $this->assertEquals($expected, $analyzer->getDeclarationsFromTokens($tokens));
    }

    /**
     * @param string $code
     * @param array  $expected
     * @param array  $useIndexes
     *
     * @dataProvider provideNamespaceUsesCases
     */
    public function testUsesFromIndexes($code, $expected, $useIndexes)
    {
        $tokens = Tokens::fromCode($code);
        $analyzer = new NamespaceUsesAnalyzer();

        $this->assertEquals($expected, $analyzer->getDeclarations($tokens, $useIndexes));
    }

    public function provideNamespaceUsesCases()
    {
        return [
            ['<?php // no uses', [], []],
            ['<?php use Foo\Bar;', [
                'Bar' => new NamespaceUseAnalysis(
                    'Foo\Bar',
                    'Bar',
                    false,
                    1,
                    6
                ),
            ], [1]],
            ['<?php use Foo\Bar; use Foo\Baz;', [
                'Bar' => new NamespaceUseAnalysis(
                    'Foo\Bar',
                    'Bar',
                    false,
                    1,
                    6
                ),
                'Baz' => new NamespaceUseAnalysis(
                    'Foo\Baz',
                    'Baz',
                    false,
                    8,
                    13
                ),
            ], [1, 8]],
            ['<?php use \Foo\Bar;', [
                'Bar' => new NamespaceUseAnalysis(
                    '\Foo\Bar',
                    'Bar',
                    false,
                    1,
                    7
                ),
            ], [1]],
            ['<?php use Foo\Bar as Baz;', [
                'Baz' => new NamespaceUseAnalysis(
                    'Foo\Bar',
                    'Baz',
                    true,
                    1,
                    10
                ),
            ], [1]],
            ['<?php use Foo\Bar as Baz; use Foo\Buz as Baz;', [
                'Baz' => new NamespaceUseAnalysis(
                    'Foo\Buz',
                    'Baz',
                    true,
                    12,
                    21
                ),
            ], [1, 12]],
        ];
    }
}
