--TEST--
Inheriting method patches (https://github.com/antecedent/patchwork/issues/4#issuecomment-11527239)

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Inheritance.php";

$foo = new FooObject;
$bar = new BarObject;
$baz = new BazObject;

assert($foo->getFoo() === "foo");

assert($bar->getFoo() === "foo");
assert($bar->getBar() === "bar");

assert($baz->getFoo() === "foo");
assert($baz->getBar() === "bar (overridden)");
assert($baz->getBaz() === "baz");

Patchwork\replace("BarObject::getFoo", function() {
    return "foo (patched)";
});

Patchwork\replace("BarObject::getBar", function() {
    return "bar (patched)";
});

assert($foo->getFoo() === "foo");

assert($bar->getFoo() === "foo (patched)");
assert($bar->getBar() === "bar (patched)");

assert($baz->getFoo() === "foo (patched)");
assert($baz->getBar() === "bar (overridden)");
assert($baz->getBaz() === "baz");

?>
===DONE===

--EXPECT--
===DONE===
