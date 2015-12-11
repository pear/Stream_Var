**********
Stream_Var
**********
Allow stream based access to any variable.

``Stream_Var`` can be registered as a stream with ``stream_register_wrapper()``
and allows stream based acces to variables in any scope.

Arrays are treated as directories, so it is possible to replace
temporary directories and files in your application with variables.


=====
Usage
=====
One example use case for ``Stream_Var`` is temporarily modifying data for
code that only uses file functions::

    <?php
    require_once 'Stream/Var.php';
    stream_wrapper_register('var', 'Stream_Var');

    $GLOBALS['somefile'] = "blah blah blah\n";

    echo file_get_contents('var://GLOBALS/somefile');
    //outputs "blah blah blah\n"
    ?>


============
Installation
============

PEAR
====
::

    $ pear install stream_var


Composer
========
::

    $ composer require pear/stream_var


=====
Links
=====
Homepage
  http://pear.php.net/package/Stream_Var
Bug tracker
  http://pear.php.net/bugs/search.php?cmd=display&package_name[]=Stream_Var
Documentation
  http://pear.php.net/manual/en/package.streams.stream-var.php
Unit test status
  https://travis-ci.org/pear/Stream_Var

  .. image:: https://travis-ci.org/pear/Stream_Var.svg?branch=master
     :target: https://travis-ci.org/pear/Stream_Var
Packagist
  https://packagist.org/packages/pear/stream_var
