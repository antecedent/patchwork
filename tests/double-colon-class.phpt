--TEST--
Compatibility with ::class syntax (https://github.com/antecedent/patchwork/issues/14)

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.5", ">=")
      or die("skip because ::class syntax is unsupported in this version of PHP") ?>

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

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
