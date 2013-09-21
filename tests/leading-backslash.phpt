--TEST--
Referring to namespaced functions with and without a leading backslash (https://github.com/antecedent/patchwork/issues/4)

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamespacedFunctions.php";

assert(Foo\identify() === "Foo");
assert(Bar\identify() === "Bar");
assert(Foo\FooClass::identify() === "Foo");
assert(Bar\BarClass::identify() === "Bar");

# No leading backslash

Patchwork\replace("Foo\identify", function() {
    return "Spam";
});

Patchwork\replace(array("Foo\FooClass", "identify"), function() {
    return "Spam";
});

assert(Foo\identify() === "Spam");
assert(Foo\FooClass::identify() === "Spam");

# Leading backslash

Patchwork\replace("\Bar\identify", function() {
    return "Eggs";
});

Patchwork\replace(array("\Bar\BarClass", "identify"), function() {
    return "Eggs";
});

assert(Bar\identify() === "Eggs");
assert(Bar\BarClass::identify() === "Eggs");

?>
===DONE===

--EXPECT--
===DONE===
