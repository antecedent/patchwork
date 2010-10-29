--TEST--
Retrieving call details from a patch

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\patch("NamedObject::getName", function() {
    $trace = Patchwork\traceCall();
    assert(count($trace) === 2);
    $properties = Patchwork\getCallProperties();
    assert($properties === reset($trace));
    assert($properties["function"] === Patchwork\getCallProperty("function"));
    assert(Patchwork\getCallProperty("function") === "getName");
    return "bar";
});

function getNameOfNamedObject()
{
    global $foo;
    return $foo->getName();
}

assert(getNameOfNamedObject() === "bar");

try {
    Patchwork\traceCall();
    assert(false);
} catch (Patchwork\Exceptions\NoCallToTrace $e) {
    assert(true);
}
    
?>
===DONE===

--EXPECT--
===DONE===