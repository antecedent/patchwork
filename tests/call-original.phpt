--TEST--
Calling the original function/method from a redefinition

--FILE--
<?php

use Patchwork as p;

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Singleton.php";
require __DIR__ . "/includes/Functions.php";
require __DIR__ . "/includes/Inheritance.php";

p\replace("Singleton::getInstance", function() {
	echo "One", PHP_EOL;
	assert(p\callOriginal() instanceof Singleton);
	echo "Two", PHP_EOL;
	return "booyah";
});

foreach (range(1, 2) as $i) {
	assert(Singleton::getInstance() === "booyah");
}

p\replace("identity", function($x) {
	echo "Four", PHP_EOL;
	assert(p\callOriginal(array(42)) === 42);
	echo "Five", PHP_EOL;
	return $x + 1;
});

assert(identity(15) === 16);

p\replace("TeenageSingletonChild::getInstance", function() {
	echo "Six", PHP_EOL;
	assert(p\callOriginal() === "Y'ain't gettin' no instance from me");
	echo "Seven", PHP_EOL;
});

TeenageSingletonChild::getInstance();

p\replace("FooObject::bah", function() {
	return p\callOriginal() . " :)";
});

$foo = new FooObject;

assert($foo->getFoo() === "foo :)");

?>
===DONE===

--EXPECT--
One
Two
One
Two
Four
Five
Six
Seven
===DONE===
