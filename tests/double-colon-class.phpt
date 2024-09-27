--TEST--
Compatibility with ::class syntax (https://github.com/antecedent/patchwork/issues/14)

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/DoubleColonClass.php";

assert(DoubleColonClass::identifySelf() === "DoubleColonClass");

Patchwork\replace("DoubleColonClass::identifySelf", function() {
    return "DisorientedClass";
});

assert(DoubleColonClass::identifySelf() === "DisorientedClass");

?>
===DONE===

--EXPECT--
===DONE===
