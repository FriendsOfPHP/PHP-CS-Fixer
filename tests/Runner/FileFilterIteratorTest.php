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

namespace PhpCsFixer\Tests\Runner;

use PhpCsFixer\Cache\CacheManagerInterface;
use PhpCsFixer\FixerFileProcessedEvent;
use PhpCsFixer\Runner\FileFilterIterator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 *
 * @covers \PhpCsFixer\Runner\FileFilterIterator
 */
final class FileFilterIteratorTest extends TestCase
{
    /**
     * @param int $repeat
     *
     * @testWith [1]
     *           [2]
     *           [3]
     */
    public function testAccept($repeat)
    {
        $file = __FILE__;
        $content = file_get_contents($file);
        $events = array();

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            FixerFileProcessedEvent::NAME,
            static function ($event) use (&$events) {
                $events[] = $event;
            }
        );

        $cache = $this->prophesize(CacheManagerInterface::class);
        $cache->needFixing($file, $content)->willReturn(true);

        $fileInfo = new \SplFileInfo($file);

        $filter = new FileFilterIterator(
            new \ArrayIterator(array_fill(0, $repeat, $fileInfo)),
            $eventDispatcher,
            $cache->reveal()
        );

        $this->assertCount(0, $events);

        $files = iterator_to_array($filter);

        $this->assertCount(1, $files);
        $this->assertSame($fileInfo, reset($files));
    }

    public function testEmitSkipEventWhenCacheNeedFixingFalse()
    {
        $file = __FILE__;
        $content = file_get_contents($file);
        $events = array();

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            FixerFileProcessedEvent::NAME,
            static function ($event) use (&$events) {
                $events[] = $event;
            }
        );

        $cache = $this->prophesize(CacheManagerInterface::class);
        $cache->needFixing($file, $content)->willReturn(false);

        $filter = new FileFilterIterator(
            new \ArrayIterator(array(new \SplFileInfo($file))),
            $eventDispatcher,
            $cache->reveal()
        );

        $this->assertCount(0, $filter);
        $this->assertCount(1, $events);

        /** @var FixerFileProcessedEvent $event */
        $event = reset($events);

        $this->assertInstanceOf(FixerFileProcessedEvent::class, $event);
        $this->assertSame(FixerFileProcessedEvent::STATUS_SKIPPED, $event->getStatus());
    }

    public function testIgnoreEmptyFile()
    {
        $file = __DIR__.'/../Fixtures/empty.php';
        $content = file_get_contents($file);
        $events = array();

        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            FixerFileProcessedEvent::NAME,
            static function ($event) use (&$events) {
                $events[] = $event;
            }
        );

        $cache = $this->prophesize(CacheManagerInterface::class);
        $cache->needFixing($file, $content)->willReturn(true);

        $filter = new FileFilterIterator(
            new \ArrayIterator(array(new \SplFileInfo($file))),
            $eventDispatcher,
            $cache->reveal()
        );

        $this->assertCount(0, $filter);
        $this->assertCount(1, $events);

        /** @var FixerFileProcessedEvent $event */
        $event = reset($events);

        $this->assertInstanceOf(FixerFileProcessedEvent::class, $event);
        $this->assertSame(FixerFileProcessedEvent::STATUS_SKIPPED, $event->getStatus());
    }

    public function testIgnore()
    {
        $eventDispatcher = new EventDispatcher();
        $eventDispatcher->addListener(
            FixerFileProcessedEvent::NAME,
            function () {
                throw new \Exception('No event expected.');
            }
        );

        $filter = new FileFilterIterator(
            new \ArrayIterator(array(
                new \SplFileInfo(__DIR__),
                new \SplFileInfo('__INVALID__'),
            )),
            $eventDispatcher,
            $this->prophesize(CacheManagerInterface::class)->reveal()
        );

        $this->assertCount(0, $filter);
    }

    public function testWithoutDispatcher()
    {
        $file = __FILE__;
        $content = file_get_contents($file);

        $cache = $this->prophesize(CacheManagerInterface::class);
        $cache->needFixing($file, $content)->willReturn(false);

        $filter = new FileFilterIterator(
            new \ArrayIterator(array(new \SplFileInfo($file))),
            null,
            $cache->reveal()
        );

        $this->assertCount(0, $filter);
    }

    public function testInvalidIterator()
    {
        $filter = new FileFilterIterator(
            new \ArrayIterator(array(__FILE__)),
            null,
            $this->prophesize(CacheManagerInterface::class)->reveal()
        );

        $this->setExpectedExceptionRegExp(
            \RuntimeException::class,
            '#^Expected instance of "\\\SplFileInfo", got "string"\.$#'
        );

        iterator_to_array($filter);
    }
}
