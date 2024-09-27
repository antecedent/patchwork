--TEST--
Calling the original function/method from a redefinition

--FILE--
<?php

use Patchwork as p;

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Singleton.php";
require __DIR__ . "/includes/Functions.php";
require __DIR__ . "/includes/Inheritance.php";

p\replace("Singleton::getInstance", function() {
	print("One\n");
	assert(p\relay() instanceof Singleton);
	print("Two\n");
	return "booyah";
});

foreach (range(1, 2) as $i) {
	assert(Singleton::getInstance() === "booyah");
}

p\replace("identity", function($x) {
	print("Four\n");
	assert(p\relay([42]) === 42);
	print("Five\n");
	return $x + 1;
});

assert(identity(15) === 16);

p\replace("TeenageSingletonChild::getInstance", function() {
	print("Six\n");
	assert(p\relay() === "Y'ain't gettin' no instance from me");
	print("Seven\n");
});

TeenageSingletonChild::getInstance();

p\replace("FooObject::bah", function() {
	return p\relay() . " :)";
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
