<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\CS\Tests\Fixer\Contrib;

use Symfony\CS\Test\AbstractFixerTestCase;

/**
 * @author Vladimir Reznichenko <kalessil@gmail.com>
 *
 * @internal
 */
final class RandomApiMigrationFixerTest extends AbstractFixerTestCase
{
    /**
     * @expectedException \Symfony\CS\ConfigurationException\InvalidFixerConfigurationException
     */
    public function testConfigureCheckSearchFunction()
    {
        $this->getFixer()->configure(array('is_null' => 'random_int'));
    }

    /**
     * @expectedException \Symfony\CS\ConfigurationException\InvalidFixerConfigurationException
     */
    public function testConfigureCheckReplacementType()
    {
        $this->getFixer()->configure(array('rand' => null));
    }

    public function testConfigure()
    {
        $this->getFixer()->configure(array('rand' => 'random_int'));

        /** @var $replacements string[] */
        $replacements = static::getStaticAttribute('\Symfony\CS\Fixer\Contrib\RandomApiMigrationFixer', 'replacements');
        static::assertSame($replacements['rand'], 'random_int');
    }

    /**
     * @dataProvider provideCases
     */
    public function testFix($expected, $input = null)
    {
        $this->doTest($expected, $input);
    }

    /**
     * @return array[]
     */
    public function provideCases()
    {
        $cases = array(
            array('<?php $smth->srand($a);'),
            array('<?php srandSmth($a);'),
            array('<?php smth_srand($a);'),
            array('<?php new srand($a);'),
            array('<?php new Smth\\srand($a);'),
            array('<?php Smth\\srand($a);'),
            array('<?php namespace\\srand($a);'),
            array('<?php Smth::srand($a);'),
            array('<?php new srand\\smth($a);'),
            array('<?php srand::smth($a);'),
            array('<?php srand\\smth($a);'),
            array('<?php "SELECT ... srand(\$a) ...";'),
            array('<?php "SELECT ... SRAND($a) ...";'),
            array("<?php 'test'.'srand' . 'in concatenation';"),
            array('<?php "test" . "srand"."in concatenation";'),
            array(
            '<?php
class SrandClass
{
public function srand($srand)
{
    if (!defined("srand") || $srand instanceof srand) {
        const srand = 1;
    }
    echo srand;
}
}

class srand extends SrandClass{
const srand = "srand"
}
', ),
            array('<?php mt_srand($a);', '<?php srand($a);'),
            array('<?php \\mt_srand($a);', '<?php \\srand($a);'),
            array('<?php $a = &mt_srand($a);', '<?php $a = &srand($a);'),
            array('<?php $a = &\\mt_srand($a);', '<?php $a = &\\srand($a);'),
            array('<?php /* foo */ mt_srand /** bar */ ($a);', '<?php /* foo */ srand /** bar */ ($a);'),
            array('<?php a(mt_srand());', '<?php a(srand());'),
            array('<?php a(\\mt_srand());', '<?php a(\\srand());'),

            // test cases for overridden configuration
            array('<?php random_int(random_int($a));', '<?php rand(rand($a));'),
            array('<?php random_int(\Other\Scope\mt_rand($a));', '<?php rand(\Other\Scope\mt_rand($a));'),
        );

        return $cases;
    }
}
