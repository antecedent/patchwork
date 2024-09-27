--TEST--
Retrieving call details from inside a patch

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

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

--EXPECT--
===DONE===
