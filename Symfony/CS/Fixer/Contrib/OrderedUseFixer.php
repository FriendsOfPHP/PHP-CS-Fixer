<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Fixer\Contrib;

use Symfony\CS\AbstractOrderedUseFixer;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Graham Campbell <graham@mineuk.com>
 */
class OrderedUseFixer extends AbstractOrderedUseFixer
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Ordering use statements alphabetically.';
    }

    /**
     * {@inheritdoc}
     */
    public static function sortingCallBack(array $first, array $second)
    {
        return static::sortAlphabetically($first[0], $second[0]);
    }
}
