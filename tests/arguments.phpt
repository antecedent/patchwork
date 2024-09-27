--TEST--
Accessing and altering arguments from patches

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\replace("setArrayElement", function(array &$array, $key, $value) {
    $array[$key] = $value;
});

$array = [0, 1, "foo" => 2, 3];

setArrayElement($array, "foo", "bar");

assert($array == [0, 1, "foo" => "bar", 3]);

?>
===DONE===

--EXPECT--
===DONE===
