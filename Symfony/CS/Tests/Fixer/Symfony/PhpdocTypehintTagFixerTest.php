<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tests\Fixer\Symfony;

use Symfony\CS\Tests\Fixer\AbstractFixerTestBase;

/**
 * @author Graham Campbell <graham@mineuk.com>
 * @author Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * @internal
 */
final class PhpdocTypehintTagFixerTest extends AbstractFixerTestBase
{
    /**
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $fixer = $this->getFixer();

        $fixer->configure(array(
            'annotation' => 'var',
        ));
        $this->makeTest($expected, $input, null, $fixer);

        $fixer->configure(array(
            'annotation' => 'type',
        ));
        list($expected, $input) = array($input ?: $expected, $input ? $expected : null);
        $this->makeTest($expected, $input, null, $fixer);
    }

    /**
     * Cases are providen for standard configuration - var annotation is expected.
     * For type annotation rotate $expected with $input.
     */
    public function provideCases()
    {
        return array(
            array(
                '<?php
    /**
     *
     */',
            ),
            array(
                '<?php
    /**
     * @var string Hello!
     */',
                '<?php
    /**
     * @type string Hello!
     */',
            ),
            array(
                '<?php /** @var string Hello! */',
                '<?php /** @type string Hello! */',
            ),
        );
    }
}
