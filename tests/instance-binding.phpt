--TEST--
Automatic binding of patches to object instances

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");
$bar = new NamedObject("bar");

assert($foo->getName() === "foo");
assert($bar->getName() === "bar");

Patchwork\replace([$foo, "getName"], function() {
    return "patched foo";
});

assert($foo->getName() === "patched foo");
assert($bar->getName() === "bar");

?>
===DONE===

--EXPECT--
===DONE===
