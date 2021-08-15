--TEST--
Automatic binding of $this on closures used as method redefinitions

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
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
