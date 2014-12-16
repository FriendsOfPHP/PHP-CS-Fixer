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
 */
class PhpdocSeparationFixerTest extends AbstractFixerTestBase
{
    public function testFix()
    {
        $expected = <<<'EOF'
<?php
    /**
     * @param EngineInterface $templating
     *
     * @return void
     */

EOF;

        $input = <<<'EOF'
<?php
    /**
     * @param EngineInterface $templating
     * @return void
     */

EOF;

        $this->makeTest($expected, $input);
    }

    public function testFixMoreTags()
    {
        $expected = <<<'EOF'
<?php
    /**
     * Hello there!
     *
     * @param string $foo
     *
     * @throws Exception
     *
     * @return bool
     */

EOF;

        $input = <<<'EOF'
<?php
    /**
     * Hello there!
     * @param string $foo
     * @throws Exception
     * @return bool
     */

EOF;

        $this->makeTest($expected, $input);
    }

    public function testFixSpreadOut()
    {
        $expected = <<<'EOF'
<?php
    /**
     * Hello there!
     *
     * Long description
     * goes here.
     *
     * @param string $foo
     * @param bool   $bar Bar
     *
     * @throws Exception|RuntimeException
     *
     * @return bool
     */

EOF;

        $input = <<<'EOF'
<?php
    /**
     * Hello there!
     *
     * Long description
     * goes here.
     * @param string $foo
     *
     *
     * @param bool   $bar Bar
     *
     *
     *
     * @throws Exception|RuntimeException
     *
     *
     *
     *
     * @return bool
     */

EOF;

        $this->makeTest($expected, $input);
    }

    public function testMultiLineComments()
    {
        $expected = <<<'EOF'
<?php
    /**
     * Hello there!
     *
     * Long description
     * goes here.
     *
     * @param string $foo test 123
     *                    asdasdasd
     * @param bool  $bar qwerty
     *
     * @throws Exception|RuntimeException
     *
     * @return bool
     */

EOF;

        $input = <<<'EOF'
<?php
    /**
     * Hello there!
     *
     * Long description
     * goes here.
     * @param string $foo test 123
     *                    asdasdasd
     * @param bool  $bar qwerty
     * @throws Exception|RuntimeException
     * @return bool
     */

EOF;

        $this->makeTest($expected, $input);
    }
}
