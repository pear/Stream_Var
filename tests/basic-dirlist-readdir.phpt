--TEST--
Read all variables in an array with glob()
--FILE--
<?php
require_once 'Stream/Var.php';
stream_wrapper_register('var', 'Stream_Var');

$GLOBALS['test'] = array(
    'one' => 1,
    'two' => 2,
    'three' => 3,
    'forty' => 40
);

$dir = opendir('var://GLOBALS/test');
while ($entry = readdir($dir)) {
    echo $entry . "\n";
}
closedir($dir);
?>
--EXPECT--
one
two
three
forty
