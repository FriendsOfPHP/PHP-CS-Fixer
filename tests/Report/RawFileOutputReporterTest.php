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

namespace PhpCsFixer\Tests\Report;

use PhpCsFixer\Report\RawFileOutputReporter;
use PhpCsFixer\Report\ReportSummary;
use PhpCsFixer\Tests\TestCase;

/**
 * @internal
 * @covers \PhpCsFixer\Report\RawFileOutputReporter
 */
final class RawFileOutputReporterTest extends TestCase
{
    /**
     * @var RawFileOutputReporter
     */
    private $reporter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporter = new RawFileOutputReporter();
    }

    final public function testGetFormat(): void
    {
        static::assertSame(
            RawFileOutputReporter::NAME,
            $this->reporter->getFormat()
        );
    }

    /**
     * @dataProvider provideGenerateCases
     */
    final public function testGenerate(
        ReportSummary $reportSummary,
        ?string $expectedReport,
        ?string $expectedException = null,
        ?string $expectedExceptionMessage = null
    ): void {
        if (null !== $expectedException) {
            $this->expectException($expectedException);
            if ($expectedExceptionMessage) {
                $this->expectExceptionMessage($expectedExceptionMessage);
            }
        }

        $actualReport = $this->reporter->generate($reportSummary);
        if (null !== $expectedReport) {
            $this->assertSame($expectedReport, $actualReport);
        }
    }

    final public function provideGenerateCases(): array
    {
        return [
            [
                new ReportSummary([], 0, 0, false, false, false),
                ''
            ],
            [
                new ReportSummary(['/some/file' => []], 0, 0, false, false, false),
                null,
                \RuntimeException::class,
                'The raw format is allowed only while using with stdin.'
            ],
            [
                new ReportSummary(['php://stdin' => []], 0, 0, false, false, false),
                null,
                \RuntimeException::class,
                'The raw format can be used only with --diff option.'
            ],
            [
                new ReportSummary(['php://stdin' => ['diff' => 'some diff']], 0, 0, false, false, false),
                'some diff'
            ],
            [
                new ReportSummary(['php://stdin' => ['diff' => '<some-tag>...</some-tag>']], 0, 0, false, false, true),
                '\<some-tag>...\</some-tag>'
            ],
        ];
    }
}
