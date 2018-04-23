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

namespace PhpCsFixer\Tests\Linter;

use org\bovigo\vfs\vfsStream;
use PhpCsFixer\Linter\CachingLinter;
use PhpCsFixer\Tests\TestCase;

/**
 * @author ntzm
 *
 * @internal
 *
 * @covers \PhpCsFixer\Linter\CachingLinter
 */
final class CachingLinterTest extends TestCase
{
    /**
     * @param bool $isAsync
     *
     * @dataProvider provideIsAsyncCases
     */
    public function testIsAsync($isAsync)
    {
        $sublinter = $this->prophesize('PhpCsFixer\Linter\LinterInterface');
        $sublinter->isAsync()->willReturn($isAsync);

        $linter = new CachingLinter($sublinter->reveal());

        $this->assertSame($isAsync, $linter->isAsync());
    }

    public function provideIsAsyncCases()
    {
        return array(
            array(true),
            array(false),
        );
    }

    public function testLintFileIsCalledOnceOnSameContent()
    {
        $fs = vfsStream::setup('root', null, array(
            'foo.php' => '<?php echo "baz";',
            'bar.php' => '<?php echo "baz";',
            'baz.php' => '<?php echo "foobarbaz";',
        ));

        $result = $this->prophesize('PhpCsFixer\Linter\LintingResultInterface');

        $sublinter = $this->prophesize('PhpCsFixer\Linter\LinterInterface');
        $sublinter->lintFile($fs->url().'/foo.php')->shouldBeCalledTimes(1)->willReturn($result->reveal());
        $sublinter->lintFile($fs->url().'/bar.php')->shouldNotBeCalled();
        $sublinter->lintFile($fs->url().'/baz.php')->shouldBeCalledTimes(1)->willReturn($result->reveal());

        $linter = new CachingLinter($sublinter->reveal());

        $results = array(
            $linter->lintFile($fs->url().'/foo.php'),
            $linter->lintFile($fs->url().'/foo.php'),
            $linter->lintFile($fs->url().'/bar.php'),
            $linter->lintFile($fs->url().'/baz.php'),
        );

        $this->assertContainsOnlyInstancesOf('PhpCsFixer\Linter\LintingResultInterface', $results);
    }

    public function testLintSourceIsCalledOnceOnSameContent()
    {
        $result = $this->prophesize('PhpCsFixer\Linter\LintingResultInterface');

        $sublinter = $this->prophesize('PhpCsFixer\Linter\LinterInterface');
        $sublinter->lintSource('<?php echo "baz";')->shouldBeCalledTimes(1)->willReturn($result->reveal());
        $sublinter->lintSource('<?php echo "foobarbaz";')->shouldBeCalledTimes(1)->willReturn($result->reveal());

        $linter = new CachingLinter($sublinter->reveal());

        $results = array(
            $linter->lintSource('<?php echo "baz";'),
            $linter->lintSource('<?php echo "baz";'),
            $linter->lintSource('<?php echo "foobarbaz";'),
        );

        $this->assertContainsOnlyInstancesOf('PhpCsFixer\Linter\LintingResultInterface', $results);
    }
}