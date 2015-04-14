<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Console\Output;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Output writer for the result of FixCommand in text format.
 */
class TxtOutput extends AbstractFixerOutput
{
    public function writeChanges(array $changes)
    {
        $format = $this->output->isDecorated() ? ' (<comment>%s</comment>)' : '%s';
        $i = 1;
        foreach ($changes as $file => $fixResult) {
            $this->output->write(sprintf('%4d) %s', $i++, $file));

            if (OutputInterface::VERBOSITY_VERBOSE <= $this->verbosity) {
                $this->output->write(sprintf($format, implode(', ', $fixResult['appliedFixers'])));
            }

            if ($this->diff) {
                $this->output->writeln('');
                if ($this->output->isDecorated()) {
                    $this->output->writeln('<comment>      ---------- begin diff ----------</comment>');
                } else {
                    $this->output->writeln('      ---------- begin diff ----------');
                }

                $this->output->writeln($fixResult['diff']);
                if ($this->output->isDecorated()) {
                    $this->output->writeln('<comment>      ---------- end diff ----------</comment>');
                } else {
                    $this->output->writeln('      ---------- end diff ----------');
                }
            }

            $this->output->writeln('');
        }
    }

    public function writeError($error)
    {
        if ($this->output->isDecorated()) {
            $this->output->writeln(sprintf('<error>%s</error>', $error));
        } else {
            $this->output->writeln($error);
        }
    }

    public function writeErrors(array $errors)
    {
        $this->output->writeLn('');
        if ($this->output->isDecorated()) {
            $this->output->writeLn('<error>Files that were not fixed due to internal error:</error>');
        } else {
            $this->output->writeLn('Files that were not fixed due to internal error:');
        }

        foreach ($errors as $i => $error) {
            $this->output->writeLn(sprintf('%4d) %s', $i + 1, $error['filepath']));
        }
    }

    public function writeInfo($info)
    {
        $this->output->writeln($info);
    }

    public function writeTimings(Stopwatch $stopwatch)
    {
        if (OutputInterface::VERBOSITY_DEBUG <= $this->verbosity) {
            $this->output->writeln('Fixing time per file:');

            foreach ($stopwatch->getSectionEvents('fixFile') as $file => $event) {
                if ('__section__' === $file) {
                    continue;
                }

                $this->output->writeln(sprintf('[%.3f s] %s', $event->getDuration() / 1000, $file));
            }

            $this->output->writeln('');
        }

        $fixEvent = $stopwatch->getEvent('fixFiles');
        $this->output->writeln(sprintf('%s all files in %.3f seconds, %.3f MB memory used', $this->isDryRun ? 'Checked' : 'Fixed', $fixEvent->getDuration() / 1000, $fixEvent->getMemory() / 1024 / 1024));
    }
}
