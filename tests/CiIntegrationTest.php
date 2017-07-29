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

namespace PhpCsFixer\Tests;

use PhpCsFixer\FileRemoval;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 *
 * @requires OS Linux|Darwin
 * @coversNothing
 */
final class CiIntegrationTest extends TestCase
{
    public static $fixtureDir;

    /**
     * @var FileRemoval
     */
    private static $fileRemoval;

    private static $tmpFilePath;
    private static $tmpFileName;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$fixtureDir = __DIR__.'/Fixtures/ci-integration';

        static::executeCommand('./setUp.sh', true);

        static::$tmpFileName = 'tmp.sh';
        static::$tmpFilePath = static::$fixtureDir.'/'.static::$tmpFileName;
        file_put_contents(static::$tmpFilePath, '');
        chmod(static::$tmpFilePath, 0777);
        self::$fileRemoval = new FileRemoval();
        self::$fileRemoval->observe(static::$tmpFilePath);
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        static::executeCommand('./tearDown.sh', true);

        self::$fileRemoval->delete(static::$tmpFilePath);
    }

    public function tearDown()
    {
        parent::tearDown();

        static::executeCommand('git reset .', true);
        static::executeCommand('git checkout .', true);
        static::executeCommand('git clean -fd', true);
        static::executeCommand('git checkout master', true);
    }

    public function testIntegration()
    {
        static::executeCommand('git checkout case1', true);

        $steps = array(
            'COMMIT_RANGE="master..case1"',
            file_get_contents(__DIR__.'/../dev-tools/ci-integration/step1-changed_files.sh'),
            file_get_contents(__DIR__.'/../dev-tools/ci-integration/step2-extra_args.sh'),
            str_replace('vendor/bin/', './../../../', file_get_contents(__DIR__.'/../dev-tools/ci-integration/step3-execution.sh')),
        );

        $result1 = static::executeScript(array(
            $steps[0],
            $steps[1],
            'echo "${#CHANGED_FILES[@]}"',
            'echo "${CHANGED_FILES[@]}"',
            'echo "${CHANGED_FILES[0]}"',
            'echo "${CHANGED_FILES[1]}"',
        ), true);

        $this->assertSame(
            array(
                '2',
                'dir a/file.php dir b/file b.php',
                'dir a/file.php',
                'dir b/file b.php',
            ),
            explode("\n", rtrim($result1['output']))
        );

        $result2 = static::executeScript(array(
            $steps[0],
            $steps[1],
            $steps[2],
            'echo "${#EXTRA_ARGS[@]}"',
            'echo "${EXTRA_ARGS[@]}"',
            'echo "${EXTRA_ARGS[0]}"',
            'echo "${EXTRA_ARGS[1]}"',
            'echo "${EXTRA_ARGS[2]}"',
            'echo "${EXTRA_ARGS[3]}"',
        ), true);

        $this->assertSame(
            array(
                '4',
                '--path-mode=intersection -- dir a/file.php dir b/file b.php',
                '--path-mode=intersection',
                '--',
                'dir a/file.php',
                'dir b/file b.php',
            ),
            explode("\n", rtrim($result2['output']))
        );

        $result3 = static::executeScript(array(
            $steps[0],
            $steps[1],
            $steps[2],
            $steps[3],
        ), true);

        $optionalIncompatibilityWarning = 'PHP needs to be a minimum version of PHP 5.3.6 and maximum version of PHP 7.1.*.
Ignoring environment requirements because `PHP_CS_FIXER_IGNORE_ENV` is set. Execution may be unstable.
';
        $executionDetails = 'Loaded config default from ".php_cs.dist".
S.
Legend: ?-unknown, I-invalid file syntax, file ignored, S-Skipped, .-no changes, F-fixed, E-error';

        $this->assertRegExp(
            '/^('.preg_quote($optionalIncompatibilityWarning, '/').')?'.preg_quote($executionDetails, '/').'$/',
            trim($result3['stderr'])
        );
        $this->assertRegExp(
            '/^Checked all files in \d+\.\d+ seconds, \d+\.\d+ MB memory used$/',
            trim($result3['output'])
        );
        $this->assertSame(0, $result3['code']);
    }

    private static function executeCommand($command, $crashOnError)
    {
        $process = new Process($command, static::$fixtureDir);
        $process->run();

        $result = array(
            'code' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
        );

        if ($crashOnError && 0 !== $result['code']) {
            throw new \RuntimeException(sprintf(
                "Cannot execute `%s`:\n%s\nCode: %s\nExit text: %s\nError output: %s\nDetails:\n%s",
                $command,
                './'.static::$tmpFileName === $command
                    ? implode('', array_map(function ($line) { return "$ $line"; }, file(static::$tmpFilePath)))."\n"
                    : '',
                $result['code'],
                $process->getExitCodeText(),
                $process->getErrorOutput(),
                $result['output']
            ));
        }

        return $result;
    }

    private static function executeScript(array $scriptParts, $crashOnError)
    {
        file_put_contents(static::$tmpFilePath, implode("\n", array_merge(array('#!/usr/bin/env bash', 'set -e', ''), $scriptParts)));

        return static::executeCommand('./'.static::$tmpFileName, $crashOnError);
    }
}
