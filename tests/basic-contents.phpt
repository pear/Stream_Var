--TEST--
Read the contents of a variable
--FILE--
<?php
require_once 'Stream/Var.php';
stream_wrapper_register('var', 'Stream_Var');

$GLOBALS['somefile'] = "blah blah blah\n";
echo file_get_contents('var://GLOBALS/somefile');
?>
--EXPECT--
blah blah blah
