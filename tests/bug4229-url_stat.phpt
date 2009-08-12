--TEST--
Use file_exists() on a var stream
--FILE--
<?php
require_once 'Stream/Var.php';
stream_wrapper_register('var', 'Stream_Var');
$GLOBALS['somefile'] = 'blah blah blah';
var_dump(file_exists('var://GLOBALS/somefile'));
?>
--EXPECT--
bool(true)