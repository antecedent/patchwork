--TEST--
Automatic binding of $this on closures used as method redefinitions

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.4", ">=")
      or die('skip because closure $this binding is not supported in this version of PHP') ?>

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../patchwork.phar";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("foo");

Patchwork\replace([$foo, "getName"], function() {
	$this->name = "bar";
	Patchwork\pass();
});

assert($foo->getName() === "bar");

?>
===DONE===

--EXPECT--
===DONE===
