--TEST--
Accessing and altering arguments from patches

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

# NOTE: This patch IS the implementation of setArrayElement,
# whose original definition just throws a NotImplemented exception.
Patchwork\replace("setArrayElement", function(array &$array, $key, $value) {
    $array[$key] = $value;
    # A hopefully unsuccessful attempt to overwrite value arguments
    $key = null;
    $value = null;
});

Patchwork\replace("setArrayElement", function(array &$array, $key, $value) {
    # Was the attempt to overwrite value arguments really unsuccessful?
    assert($key === "foo");
    assert($value === "bar");
});

$array = array(0, 1, "foo" => 2, 3);

setArrayElement($array, "foo", "bar");

assert($array == array(0, 1, "foo" => "bar", 3));
    
?>
===DONE===

--EXPECT--
===DONE===
