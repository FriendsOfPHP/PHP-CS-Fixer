<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer;

use Symfony\CS\FixerInterface;
use Symfony\CS\ConfigInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class UnusedUseStatementsFixer implements FixerInterface
{
    public function fix(\SplFileInfo $file, $content)
    {
        // some fixtures are auto-generated by Symfony and may contain unused use statements
        if (false !== strpos($file, '/Fixtures/')) {
            return $content;
        }

        // [Structure] remove unused use statements
        preg_match_all('/^use (?P<class>[^\s;]+)(?:\s+as\s+(?P<alias>[^\s;]+))?\s*;/m', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (isset($match['alias'])) {
                $short = $match['alias'];
            } else {
                $parts = explode('\\', $match['class']);
                $short = array_pop($parts);
            }

            $removed = false;

            // if the namespace is the same as the current one, the use statement can be safely removed
            // but only is there is no aliases
            if (!isset($match['alias']) && preg_match('{^[^\S\n]*(?:<\?php\s+)?namespace\s+(\S+)\s*;}um', $content, $lmatch)) {
                $namespace = $lmatch[1];

                if (preg_match('{^'.str_replace('\\', '\\\\', $namespace).'\\\\[^\\\\]+$}', trim($match['class'], '\\'))) {
                    $removed = true;
                }
            }

            // if not used, the use statement can be safely removed
            if (!$removed) {
                preg_match_all('/\b'.preg_quote($short, '/').'\b/i', str_replace($match[0]."\n", '', $content), $m);
                $removed = !count($m[0]);
            }

            if ($removed) {
                $content = str_replace($match[0]."\n", '', $content);
            }
        }

        return $content;
    }

    public function getLevel()
    {
        return FixerInterface::ALL_LEVEL;
    }

    public function getPriority()
    {
        // should be run before the ExtraEmptyLinesFixer
        return 5;
    }

    public function supports(\SplFileInfo $file, ConfigInterface $config)
    {
        return 'php' === $config->getFileType($file);
    }

    public function getName()
    {
        return 'unused_use';
    }

    public function getDescription()
    {
        return 'Unused use statements must be removed.';
    }
}
