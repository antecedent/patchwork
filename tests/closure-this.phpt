--TEST--
Automatic binding of $this on closures used as method redefinitions

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\replace([$foo, "getName"], function() {
	$this->name = "bar";
	Patchwork\fallBack();
});

assert($foo->getName() === "bar");

?>
===DONE===

--EXPECT--
===DONE===
