--TEST--
Accessing and altering arguments from patches

--FILE--
<?php

require __DIR__ . "/../../Patchwork.php";
require __DIR__ . "/../includes/functions.php";

# NOTE: This patch IS the implementation of setArrayElement,
# whose original definition just throws a NotImplemented exception.
Patchwork\patch("setArrayElement", function(array &$array, $key, $value) {
    $array[$key] = $value;
});

$array = array(0, 1, "foo" => 2, 3);

setArrayElement($array, "foo", "bar");

assert($array == array(0, 1, "foo" => "bar", 3));
    
?>
===DONE===

--EXPECT--
===DONE===
