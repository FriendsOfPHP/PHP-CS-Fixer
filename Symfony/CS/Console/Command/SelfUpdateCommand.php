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

namespace Symfony\CS\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\CS\ToolInfo;

/**
 * @author Igor Wiedler <igor@wiedler.ch>
 * @author Stephane PY <py.stephane1@gmail.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
class SelfUpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('self-update')
            ->setAliases(array('selfupdate'))
            ->setDefinition(
                array(
                    new InputOption('--force', '-f', InputOption::VALUE_NONE, 'Force update to next major version if available.'),
                )
            )
            ->setDescription('Update php-cs-fixer.phar to the latest stable version.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command replace your php-cs-fixer.phar by the
latest version released on:
<comment>https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases</comment>

<info>$ php php-cs-fixer.phar %command.name%</info>

EOT
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!ToolInfo::isInstalledAsPhar()) {
            $output->writeln('<error>Self-update is available only for PHAR version.</error>');

            return 1;
        }

        $remoteTag = $this->getRemoteTag();

        if (null === $remoteTag) {
            $output->writeln('<error>Unable to determine newest version.</error>');

            return;
        }

        $currentVersion = 'v'.$this->getApplication()->getVersion();
        if ($currentVersion === $remoteTag) {
            $output->writeln('<info>php-cs-fixer is already up to date.</info>');

            return;
        }

        $remoteVersionExploded = $this->parseVersion($remoteTag);
        $currentVersionExploded = $this->parseVersion($currentVersion);

        if ($remoteVersionExploded[0] > $currentVersionExploded[0] && true !== $input->getOption('force')) {
            $output->writeln(sprintf('<info>A new major version of php-cs-fixer is available</info> (<comment>%s</comment>)', $remoteTag));
            $output->writeln(sprintf('<info>Before upgrading please read</info> https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/%d.%d/UPGRADE.md', $remoteTag[0], $remoteTag[1]));
            $output->writeln('<info>If you are ready to upgrade run this command with</info> <comment>-f</comment>');

            // test if there is a new minor version available
            $remoteTag = sprintf('v%d.%d.%d', $currentVersionExploded[0], $currentVersionExploded[1] + 1, $currentVersionExploded[0]);
            if (!$this->hasRemoteTag($remoteTag)) {
                // test if there is a new patch version available
                $remoteTag = sprintf('v%d.%d.%d', $currentVersionExploded[0], $currentVersionExploded[1], $currentVersionExploded[0] + 1);
                if (!$this->hasRemoteTag($remoteTag)) {
                    $output->writeln('<info>php-cs-fixer is already up to date.</info>');

                    return;
                }
            }
        }

        $remoteFilename = $this->buildVersionFileUrl($remoteTag);
        $localFilename = realpath($_SERVER['argv'][0]) ?: $_SERVER['argv'][0];
        $tempFilename = basename($localFilename, '.phar').'-tmp.phar';

        try {
            $copyResult = @copy($remoteFilename, $tempFilename);
            if (false === $copyResult) {
                $output->writeln(sprintf('<error>Unable to download new version %s from the server.</error>', $remoteTag));

                return 1;
            }

            chmod($tempFilename, 0777 & ~umask());

            // test the phar validity
            $phar = new \Phar($tempFilename);
            // free the variable to unlock the file
            unset($phar);
            rename($tempFilename, $localFilename);

            $output->writeln(sprintf('<info>php-cs-fixer updated</info> (<comment>%s</comment>)', $remoteTag));
        } catch (\Exception $e) {
            if (!$e instanceof \UnexpectedValueException && !$e instanceof \PharException) {
                throw $e;
            }

            unlink($tempFilename);
            $output->writeln(sprintf('<error>The download of %s is corrupt (%s).</error>', $remoteTag, $e->getMessage()));
            $output->writeln('<error>Please re-run the self-update command to try again.</error>');

            return 1;
        }
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    private function buildVersionFileUrl($tag)
    {
        return sprintf('https://github.com/FriendsOfPHP/PHP-CS-Fixer/releases/download/%s/php-cs-fixer.phar', $tag);
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    private function hasRemoteTag($tag)
    {
        $url = 'https://api.github.com/repos/FriendsOfPHP/PHP-CS-Fixer/releases/tags/'.$tag;
        stream_context_set_default(
            $this->getStreamContextOptions('HEAD')
        );

        $headers = get_headers($url);
        if (!is_array($headers) || count($headers) < 1) {
            throw new \RuntimeException(sprintf('Failed to get headers for "%s".', $url));
        }

        return 1 === preg_match('#^HTTP\/\d.\d 200#', $headers[0]);
    }

    /**
     * @return string|null
     */
    private function getRemoteTag()
    {
        $raw = file_get_contents(
            'https://api.github.com/repos/FriendsOfPHP/PHP-CS-Fixer/releases/latest',
            null,
            stream_context_create($this->getStreamContextOptions())
        );

        if (false === $raw) {
            return;
        }

        $json = json_decode($raw, true);

        if (null === $json) {
            return;
        }

        return $json['tag_name'];
    }

    /**
     * @param string $method HTTP method
     *
     * @return array
     */
    private function getStreamContextOptions($method = 'GET')
    {
        return array(
            'http' => array(
                'header' => 'User-Agent: FriendsOfPHP/PHP-CS-Fixer',
                'method' => $method,
            ),
        );
    }

    /**
     * @param string $tag version in format v?\d.\d.\d
     *
     * @return int[]
     */
    private function parseVersion($tag)
    {
        $tag = explode('.', $tag);
        if ('v' === $tag[0][0]) {
            $tag[0] = substr($tag[0], 1);
        }

        return array((int) $tag[0], (int) $tag[1], (int) $tag[2]);
    }
}
