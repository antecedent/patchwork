--TEST--
Apply scheduled patches once

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

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
