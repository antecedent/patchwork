--TEST--
Retrieving call details from inside a patch

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\replace("NamedObject::getName", function() {
    throw new RuntimeException;
});

expectException('RuntimeException', array($foo, "getName"));

Patchwork\undoAll();

expectException('Patchwork\Exceptions\StackEmpty', 'Patchwork\Stack\top');

Patchwork\replace("NamedObject::getName", function() {
    $properties = Patchwork\Stack\top();
    assert($properties["function"] === Patchwork\Stack\top("function"));
    assert(Patchwork\Stack\top("function") === "getName");
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
