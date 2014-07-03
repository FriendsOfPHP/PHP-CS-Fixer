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

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TrailingSpacesFixer implements FixerInterface
{
    public function fix(\SplFileInfo $file, $content)
    {
        // [Structure] Don't add trailing spaces at the end of lines
        return preg_replace('/[ \t]+$/m', '', $content);
    }

    public function getLevel()
    {
        // defined in PSR2 ¶2.3
        return FixerInterface::PSR2_LEVEL;
    }

    public function getPriority()
    {
        return 20;
    }

    public function supports(\SplFileInfo $file)
    {
        return 'php' === pathinfo($file->getFilename(), PATHINFO_EXTENSION);
    }

    public function getName()
    {
        return 'trailing_spaces';
    }

    public function getDescription()
    {
        return 'Remove trailing whitespace at the end of lines.';
    }
}
