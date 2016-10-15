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

namespace PhpCsFixer;

/**
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 */
final class SharedFixerConfig
{
    private $indent;
    private $lineEnding;

    public function __construct($indent = '    ', $lineEnding = "\n")
    {
        $this->indent = $indent;
        $this->lineEnding = $lineEnding;
    }

    public function getIndent()
    {
        return $this->indent;
    }

    public function getLineEnding()
    {
        return $this->lineEnding;
    }
}
