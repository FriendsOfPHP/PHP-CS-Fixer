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

namespace PhpCsFixer\Tests\Console\Output;

use PhpCsFixer\Console\Output\ErrorOutput;
use PhpCsFixer\Error\Error;
use PhpCsFixer\Linter\LintingException;
use PhpCsFixer\Tests\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @author SpacePossum
 *
 * @internal
 *
 * @covers \PhpCsFixer\Console\Output\ErrorOutput
 */
final class ErrorOutputTest extends TestCase
{
    /**
     * @param Error  $error
     * @param int    $verbosityLevel
     * @param int    $lineNumber
     * @param int    $exceptionLineNumber
     * @param string $process
     *
     * @dataProvider provideTestCases
     */
    public function testErrorOutput(Error $error, $verbosityLevel, $lineNumber, $exceptionLineNumber, $process)
    {
        $source = $error->getSource();

        $output = $this->createStreamOutput($verbosityLevel);

        $errorOutput = new ErrorOutput($output);
        $errorOutput->listErrors($process, [$error]);

        $displayed = $this->readFullStreamOutput($output);

        $startWith = sprintf(
            '
Files that were not fixed due to errors reported during %s:
   1) %s',
            $process,
            __FILE__
        );

        if ($verbosityLevel >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            $startWith .= sprintf(
                '

                            '.'
        [%s]  '.'
        %s (%d)    '.'
                            '.'
',
                get_class($source),
                $source->getMessage(),
                $source->getCode()
            );
        }

        if ($verbosityLevel >= OutputInterface::VERBOSITY_DEBUG) {
            $startWith .= sprintf(
                '
      PhpCsFixer\Tests\Console\Output\ErrorOutputTest->getErrorAndLineNumber()
        in %s at line %d
      PhpCsFixer\Tests\Console\Output\ErrorOutputTest->provideTestCases()
      ReflectionMethod->invoke()
',
                __FILE__,
                $lineNumber
            );
        }

        $this->assertStringStartsWith($startWith, $displayed);
    }

    public function provideTestCases()
    {
        $lineNumber = __LINE__;
        list($exceptionLineNumber, $error) = $this->getErrorAndLineNumber(); // note: keep call and __LINE__ separated with one line break
        ++$lineNumber;

        return [
            [$error, OutputInterface::VERBOSITY_NORMAL, $lineNumber, $exceptionLineNumber, 'VN'],
            [$error, OutputInterface::VERBOSITY_VERBOSE, $lineNumber, $exceptionLineNumber, 'VV'],
            [$error, OutputInterface::VERBOSITY_VERY_VERBOSE, $lineNumber, $exceptionLineNumber, 'VVV'],
            [$error, OutputInterface::VERBOSITY_DEBUG, $lineNumber, $exceptionLineNumber, 'DEBUG'],
        ];
    }

    public function testLintingExceptionOutputsAppliedFixersAndDiff()
    {
        $fixerName = uniqid('braces_');
        $diffSpecificContext = uniqid('added_');
        $diff = <<<EOT
--- Original
+++ New
@@ @@
 same line
-deleted
+${diffSpecificContext}
EOT;

        $lintError = new Error(Error::TYPE_LINT, __FILE__, new LintingException(), [$fixerName], $diff);

        $noDiffLintFixerName = uniqid('no_diff_');
        $noDiffLintError = new Error(Error::TYPE_LINT, __FILE__, new LintingException(), [$noDiffLintFixerName]);

        $invalidErrorFixerName = uniqid('line_ending_');
        $invalidDiff = uniqid('invalid_diff_');

        $invalidError = new Error(Error::TYPE_INVALID, __FILE__, new LintingException(), [$invalidErrorFixerName], $invalidDiff);

        $output = $this->createStreamOutput(OutputInterface::VERBOSITY_VERY_VERBOSE);

        $errorOutput = new ErrorOutput($output);
        $errorOutput->listErrors(uniqid('process_'), [
            $lintError,
            $noDiffLintError,
            $invalidError,
        ]);

        $displayed = $this->readFullStreamOutput($output);

        $this->assertContains($fixerName, $displayed);
        $this->assertContains($diffSpecificContext, $displayed);

        $this->assertContains($noDiffLintFixerName, $displayed);

        $this->assertNotContains($invalidErrorFixerName, $displayed);
        $this->assertNotContains($invalidDiff, $displayed);
    }

    /**
     * @param int $verbosityLevel
     *
     * @return StreamOutput
     */
    private function createStreamOutput($verbosityLevel)
    {
        $output = new StreamOutput(fopen('php://memory', 'wb', false));
        $output->setDecorated(false);
        $output->setVerbosity($verbosityLevel);

        return $output;
    }

    /**
     * @param StreamOutput $output
     *
     * @return string
     */
    private function readFullStreamOutput($output)
    {
        rewind($output->getStream());
        $displayed = stream_get_contents($output->getStream());
        // normalize line breaks,
        // as we output using SF `writeln` we are not sure what line ending has been used as it is
        // based on the platform/console/terminal used
        $displayed = str_replace(PHP_EOL, "\n", $displayed);

        return $displayed;
    }

    private function getErrorAndLineNumber()
    {
        $lineNumber = __LINE__;
        $exception = new \RuntimeException(// note: keep exception constructor and __LINE__ separated with one line break
            'PHPUnit RT',
            888,
            new \InvalidArgumentException('PHPUnit IAE')
        );

        return [$lineNumber + 1, new Error(Error::TYPE_EXCEPTION, __FILE__, $exception)];
    }
}
