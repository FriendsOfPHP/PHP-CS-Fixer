=================================
Rule ``blank_lines_inside_block``
=================================

There must not be blank lines at start and end of braces blocks.

Examples
--------

Example #1
~~~~~~~~~~

.. code-block:: diff

   --- Original
   +++ New
    <?php
    class Foo {
   -
        public function foo() {
   -
            if ($baz == true) {
   -
                echo "foo";
   -
            }
   -
        }
   -
    }

Rule sets
---------

The rule is part of the following rule sets:

@PhpCsFixer
  Using the `@PhpCsFixer <./../../ruleSets/PhpCsFixer.rst>`_ rule set will enable the ``blank_lines_inside_block`` rule.

@Symfony
  Using the `@Symfony <./../../ruleSets/Symfony.rst>`_ rule set will enable the ``blank_lines_inside_block`` rule.
