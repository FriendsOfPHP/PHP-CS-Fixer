===================================================
Rule ``no_singleline_whitespace_before_semicolons``
===================================================

.. warning:: This rule is deprecated and will be removed on next major version.

   You should use ``whitespace_before_statement_end`` instead.

Single-line whitespace before closing semicolon are prohibited.

Examples
--------

Example #1
~~~~~~~~~~

.. code-block:: diff

   --- Original
   +++ New
   -<?php $this->foo() ;
   +<?php $this->foo();
