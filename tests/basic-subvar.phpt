--TEST--
Read the contents of a nested variable
--FILE--
<?php
require_once 'Stream/Var.php';
stream_wrapper_register('var', 'Stream_Var');

$GLOBALS['one']['two']['three']['four'][5] = "hi!\n";
echo file_get_contents('var://GLOBALS/one/two/three/four/5');
?>
--EXPECT--
hi!
