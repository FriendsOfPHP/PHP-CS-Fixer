==============================
Rule ``no_alternative_syntax``
==============================

Replace control structure alternative syntax to use braces.

Configuration
-------------

``exclude_non_monolithic_code``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Whether to exclude from fixing code with inline HTML elements.

Allowed types: ``bool``

Default value: ``false``

Examples
--------

Example #1
~~~~~~~~~~

*Default* configuration.

.. code-block:: diff

   --- Original
   +++ New
    <?php
   -if (true):echo 't';else:echo 'f';endif;
   +if (true) { echo 't';} else { echo 'f';}

   -while (true):echo 'red';endwhile;
   +while (true) { echo 'red';}

   -for (;;):echo 'xc';endfor;
   +for (;;) { echo 'xc';}

   -foreach (array('a') as $item):echo 'xc';endforeach;
   +foreach (array('a') as $item) { echo 'xc';}

Example #2
~~~~~~~~~~

With configuration: ``['exclude_non_monolithic_code' => false]``.

.. code-block:: diff

   --- Original
   +++ New
   -<?php if (true): ?>
   +<?php if (true) { ?>
    <div>Here!</div>
   -<?php endif; ?>
   +<?php } ?>

Example #3
~~~~~~~~~~

With configuration: ``['exclude_non_monolithic_code' => false]``.

.. code-block:: diff

   --- Original
   +++ New
   -<?php if ($condition): ?>
   +<?php if ($condition) { ?>
    Lorem ipsum.
   -<?php endif; ?>
   +<?php } ?>

Example #4
~~~~~~~~~~

With configuration: ``['exclude_non_monolithic_code' => false]``.

.. code-block:: diff

   --- Original
   +++ New
   -<?php while (true): ?>
   +<?php while (true) { ?>
    <?= $string; ?>
   -<?php endwhile; ?>
   +<?php } ?>

Rule sets
---------

The rule is part of the following rule sets:

@PhpCsFixer
  Using the `@PhpCsFixer <./../../ruleSets/PhpCsFixer.rst>`_ rule set will enable the ``no_alternative_syntax`` rule with the default config.

@Symfony
  Using the `@Symfony <./../../ruleSets/Symfony.rst>`_ rule set will enable the ``no_alternative_syntax`` rule with the default config.
