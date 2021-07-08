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

use InvalidArgumentException;
use PhpCsFixer\Tests\TestCase;
use PhpCsFixer\Tokenizer\Analyzer\AlternativeSyntaxAnalyzer;
use PhpCsFixer\Tokenizer\Tokens;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Tokenizer\Analyzer\AlternativeSyntaxAnalyzer
 */
final class AlternativeSyntaxAnalyzerTest extends TestCase
{
    /**
     * @param string $code
     * @param int    $startIndex
     * @param int    $expectedResult
     *
     * @dataProvider provideFindBlockEndCases
     */
    public function testItFindsTheEndOfAnAlternativeSyntaxBlock($code, $startIndex, $expectedResult)
    {
        $analyzer = new AlternativeSyntaxAnalyzer();

        static::assertSame(
            $expectedResult,
            $analyzer->findAlternativeSyntaxBlockEnd(
                Tokens::fromCode($code),
                $startIndex
            )
        );
    }

    public function provideFindBlockEndCases()
    {
        yield ['<?php if ($foo): foo(); endif;', 1, 13];
        yield ['<?php if ($foo): foo(); else: bar(); endif;', 1, 13];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 1, 13];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 13, 25];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); else: baz(); endif;', 13, 25];
        yield ['<?php if ($foo): foo(); else: bar(); endif;', 13, 21];
        yield ['<?php for (;;): foo(); endfor;', 1, 14];
        yield ['<?php foreach ($foo as $bar): foo(); endforeach;', 1, 17];
        yield ['<?php while ($foo): foo(); endwhile;', 1, 13];
        yield ['<?php switch ($foo): case 1: foo(); endswitch;', 1, 18];
    }

    /**
     * @param string $code
     * @param int    $startIndex
     * @param string $expectedMessage
     *
     * @dataProvider provideFindInvalidBlockEndCases
     */
    public function testItThrowsOnInvalidAlternativeSyntaxBlockStartIndex($code, $startIndex, $expectedMessage)
    {
        $tokens = Tokens::fromCode($code);

        $analyzer = new AlternativeSyntaxAnalyzer();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $analyzer->findAlternativeSyntaxBlockEnd($tokens, $startIndex);
    }

    public function provideFindInvalidBlockEndCases()
    {
        yield ['<?php if ($foo): foo(); endif;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); endif;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); endif;', 999, 'There is no token at index 999.'];

        yield ['<?php if ($foo): foo(); else: bar(); endif;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); else: bar(); endif;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); else: bar(); endif;', 999, 'There is no token at index 999.'];

        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 999, 'There is no token at index 999.'];

        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); endif;', 999, 'There is no token at index 999.'];

        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); else: baz(); endif;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); else: baz(); endif;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); elseif ($bar): bar(); else: baz(); endif;', 999, 'There is no token at index 999.'];

        yield ['<?php if ($foo): foo(); else: bar(); endif;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); else: bar(); endif;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php if ($foo): foo(); else: bar(); endif;', 999, 'There is no token at index 999.'];

        yield ['<?php for (;;): foo(); endfor;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php for (;;): foo(); endfor;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php for (;;): foo(); endfor;', 999, 'There is no token at index 999.'];

        yield ['<?php foreach ($foo as $bar): foo(); endforeach;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php foreach ($foo as $bar): foo(); endforeach;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php foreach ($foo as $bar): foo(); endforeach;', 999, 'There is no token at index 999.'];

        yield ['<?php while ($foo): foo(); endwhile;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php while ($foo): foo(); endwhile;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php while ($foo): foo(); endwhile;', 999, 'There is no token at index 999.'];

        yield ['<?php switch ($foo): case 1: foo(); endswitch;', 0, 'Token at index 0 is not the start of an alternative syntax block.'];
        yield ['<?php switch ($foo): case 1: foo(); endswitch;', 2, 'Token at index 2 is not the start of an alternative syntax block.'];
        yield ['<?php switch ($foo): case 1: foo(); endswitch;', 999, 'There is no token at index 999.'];
    }
}
