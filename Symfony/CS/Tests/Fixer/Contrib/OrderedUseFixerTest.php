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

namespace Symfony\CS\Tests\Fixer\Contrib;

use Symfony\CS\Fixer\Contrib\OrderedUseFixer;
use Symfony\CS\Tests\Fixer\AbstractFixerTestBase;

class OrderedUseFixerTest extends AbstractFixerTestBase
{
    protected $defaultSortType = OrderedUseFixer::SORT_ALPHA;

    protected function setUp()
    {
        parent::setUp();
        OrderedUseFixer::configure(array($this->defaultSortType));
    }

    protected function tearDown()
    {
        OrderedUseFixer::configure(array($this->defaultSortType));
        parent::tearDown();
    }

    public function testFix()
    {
        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar as ZooBar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar;
use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar as FooBaz;
 use Foo\Bir as FBB;
use Foo\Zar\Baz;
use SomeClass;
   use Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;
use Zoo\Bar as ZooBar;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Zoo\Tar;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar as ZooBar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar\FooBar as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
 use Foo\Bar;
use Foo\Zar\Baz;
use Symfony\Annotation\Template;
   use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testFixWithMultipleNamespace()
    {
        $expected = <<<'EOF'
<?php

namespace FooRoo {

    use Foo\Bar;
    use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar as FooBaz;
     use Foo\Bir as FBB;
    use Foo\Zar\Baz;
    use SomeClass;
       use Symfony\Annotation\Template, Zoo\Bar as ZooBar;
    use Zoo\Tar1;

    $a = new Bar();
    $a = new FooBaz();
    $a = new someclass();

    use Zoo\Tar2;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}

namespace BlaRoo {

    use Foo\Zar\Baz;
  use SomeClass;
    use Symfony\Annotation\Template;
  use Symfony\Doctrine\Entities\Entity, Zoo\Bar;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}
EOF;

        $input = <<<'EOF'
<?php

namespace FooRoo {

    use Foo\Bar\FooBar as FooBaz;
    use Zoo\Bar as ZooBar, Zoo\Tar1;
     use Foo\Bar;
    use Foo\Zar\Baz;
    use Symfony\Annotation\Template;
       use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
    use SomeClass;

    $a = new Bar();
    $a = new FooBaz();
    $a = new someclass();

    use Zoo\Tar2;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}

namespace BlaRoo {

    use Foo\Zar\Baz;
  use Zoo\Bar;
    use SomeClass;
  use Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testFixWithComment()
    {
        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar;
use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar /* He there */ as FooBaz;
 use Foo\Bir as FBB;
use Foo\Zar\Baz;
use SomeClass;
   use /* FIXME */Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;
use Zoo\Bar as ZooBar;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Zoo\Tar;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar\FooBar /* He there */ as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
 use Foo\Bar;
use Foo\Zar\Baz;
use /* FIXME */Symfony\Annotation\Template;
   use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    /**
     * @requires PHP 5.4
     */
    public function test54()
    {
        $expected = <<<'EOF'
<?php

use Foo\Bar;
use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar as FooBaz;
 use Foo\Bir as FBB;
use Foo\Zar\Baz;
use SomeClass;
   use Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;
use Zoo\Bar as ZooBar;

use Zoo\Tar;

trait Foo {}

trait Zoo {}

class AnnotatedClass
{
    use Foo, Bar;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
<?php

use Foo\Bar\FooBar as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
 use Foo\Bar;
use Foo\Zar\Baz;
use Symfony\Annotation\Template;
   use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

use Symfony\Doctrine\Entities\Entity;

trait Foo {}

trait Zoo {}

class AnnotatedClass
{
    use Foo, Bar;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    /**
     * @requires PHP 5.4
     */
    public function testFixWithTraitImports()
    {
        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Acme\MyReusableTrait;
use Foo\Bar, Foo\Bar\Foo as Fooo;
 use Foo\Bar\FooBar as FooBaz;
use Foo\Bir as FBB;
use Foo\Zar\Baz;
use SomeClass;
   use Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;
use Zoo\Bar as ZooBar;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Zoo\Tar;

class AnnotatedClass
{
    use MyReusableTrait;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $baz) {};
    }
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar\FooBar as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
 use Foo\Bar;
use Foo\Zar\Baz;
use Acme\MyReusableTrait;
use Symfony\Annotation\Template;
   use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    use MyReusableTrait;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $baz) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testFixWithDifferentCases()
    {
        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Baz;
use abc\Bar;

<?php

use abc\Bar;
use Zoo\Baz;

class Test
{
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Baz;
use abc\Bar;

<?php

use Zoo\Baz;
use abc\Bar;

class Test
{
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testWithoutUses()
    {
        $expected = <<<'EOF'
<?php

$c = 1;
EOF
        ;

        $this->makeTest($expected);
    }

    public function testOrderWithTrailingDigit()
    {
        $expected = <<<'EOF'
<?php

use abc\Bar;
use abc2\Bar2;
use xyz\abc\Bar6;
use xyz\abc2\Bar7;
use xyz\xyz\Bar4;
use xyz\xyz\Bar5;

class Test
{
}
EOF;

        $input = <<<'EOF'
<?php

use abc2\Bar2;
use abc\Bar;
use xyz\abc2\Bar7;
use xyz\abc\Bar6;
use xyz\xyz\Bar4;
use xyz\xyz\Bar5;

class Test
{
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testCodeWithImportsOnly()
    {
        $expected = <<<'EOF'
<?php

use Aaa;
use Bbb;
EOF;

        $input = <<<'EOF'
<?php

use Bbb;
use Aaa;
EOF;

        $this->makeTest($expected, $input);
    }

    public function testCodeWithCloseTag()
    {
        $this->makeTest(
            '<?php
                use A\C1;
                use A\D?><?php use B\C2; use E\F ?>',
            '<?php
                use A\C1;
                use B\C2?><?php use A\D; use E\F ?>'
        );
    }

    public function testCodeWithComments()
    {
        $this->makeTest(
            '<?php
                use A\C1 /* A */;
                use /* B */ B\C2;',
            '<?php
                use /* B */ B\C2;
                use A\C1 /* A */;'
        );
    }

    /**
     * @dataProvider provide70Cases
     * @requires PHP 7.0
     */
    public function test70($expected, $input = null)
    {
        $this->makeTest($expected, $input);
    }

    public function provide70Cases()
    {
        return array(
            array(
                '<?php
use A\B;
use some\a\{ClassA, ClassB, ClassC as C};
use const some\a\{ConstA, ConstB, ConstC};
use function some\a\{fn_a, fn_b, fn_c};
',
                '<?php
use some\a\{ClassA, ClassB, ClassC as C};
use function some\a\{fn_a, fn_b, fn_c};
use A\B;
use const some\a\{ConstA, ConstB, ConstC};
',
            ),
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Test sorting by length
    |--------------------------------------------------------------------------
    */

    /**
     * @expectedException \Symfony\CS\ConfigurationException\InvalidFixerConfigurationException
     * @expectedExceptionMessage [ordered_use] Sort type is invalid. Array should contain only one of the parameter: "alpha", "length"
     */
    public function testInvalidConfigWithNotSupportedSortType()
    {
        OrderedUseFixer::configure(array('dope'));
    }

    /**
     * @expectedException \Symfony\CS\ConfigurationException\InvalidFixerConfigurationException
     * @expectedExceptionMessage [ordered_use] Sort type is invalid. Array should contain only one of the parameter: "alpha", "length"
     */
    public function testInvalidConfigWithMultipleSortTypes()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_ALPHA, OrderedUseFixer::SORT_LENGTH));
    }

    /**
     * @expectedException \Symfony\CS\ConfigurationException\InvalidFixerConfigurationException
     * @expectedExceptionMessage [ordered_use] Sort type is invalid. Array should contain only one of the parameter: "alpha", "length"
     */
    public function testInvalidConfigWithSortTypeIsNotString()
    {
        OrderedUseFixer::configure(array(new \stdClass()));
    }

    /**
     * @expectedException \Symfony\CS\ConfigurationException\InvalidFixerConfigurationException
     * @expectedExceptionMessage [ordered_use] Sort type is invalid. Array should contain only one of the parameter: "alpha", "length"
     */
    public function testInvalidConfigWithEmptyArray()
    {
        OrderedUseFixer::configure(array());
    }

    public function testFixByLength()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar as ZooBar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar;
use Zoo\Tar, SomeClass;
 use Foo\Zar\Baz;
use Foo\Bir as FBB;
use Zoo\Bar as ZooBar;
   use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar as FooBaz;
use Symfony\Annotation\Template;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar as ZooBar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar\FooBar as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
 use Foo\Bar;
use Foo\Zar\Baz;
use Symfony\Annotation\Template;
   use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthFixWithSameLength()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
<?php

use Acme;
use Bar1;
use Barr;
use Fooo;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
<?php

use Acme;
use Fooo;
use Barr;
use Bar1;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthFixWithMultipleNamespace()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
<?php

namespace FooRoo {

    use Foo\Bar;
    use Zoo\Tar1, Zoo\Tar2;
    use SomeClass;
    use Foo\Zar\Baz;
    use Foo\Bir as FBB;
    use Zoo\Bar as ZooBar, Foo\Bar\Foo as Fooo;
    use Foo\Bar\FooBar as FooBaz;

    $a = new Bar();
    $a = new FooBaz();
    $a = new someclass();

    use Symfony\Annotation\Template;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}

namespace BlaRoo {

    use Zoo\Bar;
    use SomeClass;
    use Foo\Zar\Baz;
    use Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}
EOF;

        $input = <<<'EOF'
<?php

namespace FooRoo {

    use Foo\Bar\FooBar as FooBaz;
    use Zoo\Bar as ZooBar, Zoo\Tar1;
    use Foo\Bar;
    use Foo\Zar\Baz;
    use Symfony\Annotation\Template;
    use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
    use SomeClass;

    $a = new Bar();
    $a = new FooBaz();
    $a = new someclass();

    use Zoo\Tar2;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}

namespace BlaRoo {

    use Foo\Zar\Baz;
    use Zoo\Bar;
    use SomeClass;
    use Symfony\Annotation\Template, Symfony\Doctrine\Entities\Entity;

    class AnnotatedClass
    {
        /**
         * @Template(foobar=21)
         * @param Entity $foo
         */
        public function doSomething($foo)
        {
            $bar = $foo->toArray();
            /** @var ArrayInterface $bar */

            return function () use ($bar, $foo) {};
        }
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthFixWithComment()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar;
use Zoo\Tar, SomeClass;
use Foo\Zar\Baz;
use Foo\Bir as FBB;
use Zoo\Bar as ZooBar;
use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar /* He there */ as FooBaz;
use /* FIXME */Symfony\Annotation\Template;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar\FooBar /* He there */ as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
use Foo\Bar;
use Foo\Zar\Baz;
use /* FIXME */Symfony\Annotation\Template;
use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    /**
     * @requires PHP 5.4
     */
    public function testByLength54()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
<?php

use Foo\Bar;
use Zoo\Tar, SomeClass;
use Foo\Zar\Baz;
use Foo\Bir as FBB;
use Zoo\Bar as ZooBar;
use Foo\Bar\Foo as Fooo, Foo\Bar\FooBar as FooBaz;
use Symfony\Annotation\Template;

use Symfony\Doctrine\Entities\Entity;

trait Foo {}

trait Zoo {}

class AnnotatedClass
{
    use Foo, Bar;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $input = <<<'EOF'
<?php

use Foo\Bar\FooBar as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
use Foo\Bar;
use Foo\Zar\Baz;
use Symfony\Annotation\Template;
use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

use Symfony\Doctrine\Entities\Entity;

trait Foo {}

trait Zoo {}

class AnnotatedClass
{
    use Foo, Bar;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $foo) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    /**
     * @requires PHP 5.4
     */
    public function testByLengthFixWithTraitImports()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar;
use Zoo\Tar, SomeClass;
use Foo\Zar\Baz;
use Foo\Bir as FBB;
use Zoo\Bar as ZooBar;
use Foo\Bar\Foo as Fooo;
use Acme\MyReusableTrait, Foo\Bar\FooBar as FooBaz;
use Symfony\Annotation\Template;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    use MyReusableTrait;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $baz) {};
    }
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Bar;
use Foo\Bar;
use Foo\Zar\Baz;

<?php

use Foo\Bar\FooBar as FooBaz;
use Zoo\Bar as ZooBar, Zoo\Tar;
use Foo\Bar;
use Foo\Zar\Baz;
use Acme\MyReusableTrait;
use Symfony\Annotation\Template;
use Foo\Bar\Foo as Fooo, Foo\Bir as FBB;
use SomeClass;

$a = new Bar();
$a = new FooBaz();
$a = new someclass();

use Symfony\Doctrine\Entities\Entity;

class AnnotatedClass
{
    use MyReusableTrait;

    /**
     * @Template(foobar=21)
     * @param Entity $foo
     */
    public function doSomething($foo)
    {
        $bar = $foo->toArray();
        /** @var ArrayInterface $bar */

        return function () use ($bar, $baz) {};
    }
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthFixWithDifferentCases()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Baz;
use abc\Bar;

<?php

use abc\Bar;
use Zoo\Baz;

class Test
{
}
EOF;

        $input = <<<'EOF'
The normal
use of this fixer
should not change this sentence nor those statements below
use Zoo\Baz;
use abc\Bar;

<?php

use Zoo\Baz;
use abc\Bar;

class Test
{
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthOrderWithTrailingDigit()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
<?php

use abc\Bar;
use abc2\Bar2;
use xyz\abc\Bar6;
use xyz\xyz\Bar4;
use xyz\xyz\Bar5;
use xyz\abc2\Bar7;

class Test
{
}
EOF;

        $input = <<<'EOF'
<?php

use abc2\Bar2;
use abc\Bar;
use xyz\abc2\Bar7;
use xyz\abc\Bar6;
use xyz\xyz\Bar4;
use xyz\xyz\Bar5;

class Test
{
}
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthCodeWithImportsOnly()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
<?php

use Aaa;
use Bbb;
EOF;

        $input = <<<'EOF'
<?php

use Bbb;
use Aaa;
EOF;

        $this->makeTest($expected, $input);
    }

    public function testByLengthWithoutUses()
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));

        $expected = <<<'EOF'
<?php

$c = 1;
EOF
        ;

        $this->makeTest($expected);
    }

    /**
     * @dataProvider provide70CasesByLength
     * @requires PHP 7.0
     */
    public function test70ByLength($expected, $input = null)
    {
        OrderedUseFixer::configure(array(OrderedUseFixer::SORT_LENGTH));
        $this->makeTest($expected, $input);
    }

    public function provide70CasesByLength()
    {
        return array(
            array(
                '<?php
use A\B;
use function some\a\{fn_a, fn_b, fn_c};
use some\a\{ClassA, ClassB, ClassC as C};
use some\b\{ClassA, ClassB, ClassC as C};
use const some\a\{ConstA, ConstB, ConstC};
use const some\a\{ConstB, ConstA, ConstC};
',
                '<?php
use some\a\{ClassA, ClassB, ClassC as C};
use function some\a\{fn_a, fn_b, fn_c};
use some\b\{ClassA, ClassB, ClassC as C};
use const some\a\{ConstB, ConstA, ConstC};
use A\B;
use const some\a\{ConstA, ConstB, ConstC};
',
            ),
        );
    }
}
