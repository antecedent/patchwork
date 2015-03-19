--TEST--
Apply scheduled patches once

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

$callNumber = 0;
Patchwork\replace("NamedObject::getName", function () use (&$callNumber) {
    $callNumber++;
});

require __DIR__ . "/includes/TestUtils.php"; # (dummy import)
require __DIR__ . "/includes/NamedObject.php";
require __DIR__ . "/includes/Inheritance.php"; # (dummy import)

$obj= new NamedObject('');
$obj->getName();

assert($callNumber === 1);

?>
===DONE===

--EXPECT--
===DONE===
