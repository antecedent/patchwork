--TEST--
Retrieving call details from inside a patch

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\replace("NamedObject::getName", function() {
    throw new RuntimeException;
});

expectException('RuntimeException', array($foo, "getName"));

Patchwork\undoAll();

expectException('Patchwork\Exceptions\StackEmpty', 'Patchwork\top');

Patchwork\replace("NamedObject::getName", function() {
    $properties = Patchwork\top();
    assert($properties["function"] === Patchwork\top("function"));
    assert(Patchwork\top("function") === "getName");
    return "bar";
});

function getNameOfNamedObject()
{
    global $foo;
    return $foo->getName();
}

assert(getNameOfNamedObject() === "bar");

expectException('Patchwork\Exceptions\StackEmpty', 'Patchwork\top');
    
?>
===DONE===

--EXPECT--
===DONE===
