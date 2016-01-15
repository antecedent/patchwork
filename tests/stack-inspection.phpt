--TEST--
Retrieving call details from inside a patch

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\replace("NamedObject::getName", function() {
    throw new RuntimeException;
});

expectException('RuntimeException', [$foo, "getName"]);

Patchwork\undoAll();

expectException('Patchwork\Exceptions\StackEmpty', 'Patchwork\Stack\top');

Patchwork\replace("NamedObject::getName", function() {
    assert(Patchwork\getFunction() === "getName");
    return "bar";
});

function getNameOfNamedObject()
{
    global $foo;
    return $foo->getName();
}

assert(getNameOfNamedObject() === "bar");

expectException('Patchwork\Exceptions\StackEmpty', 'Patchwork\Stack\top');

?>
===DONE===

--EXPECTF--
Warning: Please import Patchwork from a point in your code where no user-defined function, class or trait is yet defined. %s() and possibly others currently violate this. in %s on line %d
===DONE===
