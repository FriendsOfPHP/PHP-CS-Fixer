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

namespace PhpCsFixer\Test;

use PhpCsFixer\Tests\Test\AbstractIntegrationTestCase as BaseAbstractIntegrationTestCase;

/**
 * @TODO 3.0 While removing, `gecko-packages/gecko-php-unit` shall be moved from `require` to `require-dev`.
 *
 * @deprecated since v2.4, use PhpCsFixer\Tests\Test\AbstractIntegrationTestCase instead
 */
abstract class AbstractIntegrationTestCase extends BaseAbstractIntegrationTestCase
{
    public function __construct()
    {
        @trigger_error(
            sprintf(
                'The "%s" class is deprecated. You should stop using it, as it will be removed in 3.0 version. Use "%s" instead.',
                __CLASS__,
                'PhpCsFixer\Tests\Test\AbstractIntegrationTestCase'
            ),
            E_USER_DEPRECATED
        );
    }
}
