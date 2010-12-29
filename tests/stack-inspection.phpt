--TEST--
Retrieving call details from inside a patch

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\replace("NamedObject::getName", function() {
    $trace = Patchwork\Stack\all();
    assert(count($trace) === 2);
    $properties = Patchwork\Stack\top();
    assert($properties === reset($trace));
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

try {
    Patchwork\Stack\all();
    assert(false);
} catch (Patchwork\Exceptions\StackEmpty $e) {
    assert(true);
}
    
?>
===DONE===

--EXPECT--
===DONE===