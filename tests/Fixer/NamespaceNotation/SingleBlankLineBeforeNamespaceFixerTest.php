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

namespace PhpCsFixer\Tests\Fixer\NamespaceNotation;

use PhpCsFixer\Tests\Test\AbstractFixerTestCase;
use PhpCsFixer\WhitespacesFixerConfig;

/**
 * @author Graham Campbell <graham@alt-three.com>
 *
 * @internal
 *
 * @covers \PhpCsFixer\AbstractLinesBeforeNamespaceFixer
 * @covers \PhpCsFixer\Fixer\NamespaceNotation\SingleBlankLineBeforeNamespaceFixer
 */
final class SingleBlankLineBeforeNamespaceFixerTest extends AbstractFixerTestCase
{
    /**
     * @dataProvider provideFixCases
     *
     * @param string                      $expected
     * @param null|string                 $input
     * @param null|WhitespacesFixerConfig $input
     */
    public function testFix($expected, $input = null, WhitespacesFixerConfig $whitespace = null)
    {
        $this->doTest($expected, $input, null, $whitespace);
    }

    /**
     * @return array
     */
    public function provideFixCases()
    {
        return array(
            array("<?php\n\nnamespace X;"),
            array("<?php\n\nnamespace X;", "<?php\n\n\n\nnamespace X;"),
            array("<?php\r\n\r\nnamespace X;"),
            array("<?php\n\nnamespace X;", "<?php\r\n\r\n\r\n\r\nnamespace X;"),
            array("<?php\n\nfoo();\nnamespace\\bar\\baz();"),
            array("<?php\n\nnamespace X;", "<?php\nnamespace X;"),
            array("<?php\n\nnamespace X;", '<?php namespace X;'),
            array("<?php\n\nnamespace X;", "<?php\t\nnamespace X;"),
            array("<?php \n\nnamespace X;"),
            array("<?php\r\n\r\nnamespace X;", '<?php namespace X;', new WhitespacesFixerConfig('    ', "\r\n")),
            array("<?php\r\n\r\nnamespace X;", "<?php\nnamespace X;", new WhitespacesFixerConfig('    ', "\r\n")),
            array("<?php\r\n\r\nnamespace X;", "<?php\n\n\n\nnamespace X;", new WhitespacesFixerConfig('    ', "\r\n")),
            array("<?php\r\n\r\nnamespace X;", "<?php\r\n\n\nnamespace X;", new WhitespacesFixerConfig('    ', "\r\n")),
        );
    }

    public function testFixExampleWithCommentTooMuch()
    {
        $expected = <<<'EOF'
<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Contrib;

EOF;

        $input = <<<'EOF'
<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace PhpCsFixer\Fixer\Contrib;

EOF;

        $this->doTest($expected, $input);
    }

    public function testFixExampleWithCommentTooLittle()
    {
        $expected = <<<'EOF'
<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace PhpCsFixer\Fixer\Contrib;

EOF;

        $input = <<<'EOF'
<?php

/*
 * This file is part of the PHP CS utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace PhpCsFixer\Fixer\Contrib;

EOF;

        $this->doTest($expected, $input);
    }
}
