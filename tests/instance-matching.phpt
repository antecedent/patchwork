--TEST--
Automatic object instance matching

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

$foo = new NamedObject("Foo");
$bar = new NamedObject("Bar");

Patchwork\filter(array($bar, "getName"), function() {
    echo "Filtered: ";
});

echo $foo->getName(), PHP_EOL;
echo $bar->getName(), PHP_EOL;

?>

--EXPECT--
Foo
Filtered: Bar