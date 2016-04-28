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
 * @author Andreas Möller <am@localheinz.com>
 *
 * @internal
 */
interface CacheHandler
{
    /**
     * @return bool
     */
    public function willCache();

    /**
     * @return string
     */
    public function read();

    /**
     * @param string $content
     */
    public function write($content);
}
