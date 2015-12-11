--TEST--
Return false for file_exists on a non-existing variable
--FILE--
<?php
require_once 'Stream/Var.php';
stream_wrapper_register('var', 'Stream_Var');

$GLOBALS['somefile'] = 'blah blah blah';
var_dump(file_exists('var://GLOBALS/somefile'));
var_dump(file_exists('var://GLOBALS/otherfile'));
?>
--EXPECT--
bool(true)
bool(false)