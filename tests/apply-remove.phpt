--TEST--
Applying and removing patches

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Singleton.php";

$real = Singleton::getInstance();
$fake = new Singleton;

assert(Singleton::getInstance() === $real);

$handle = Patchwork\replace("Singleton::getInstance", function() use ($fake) {
    return $fake;
});

assert(Singleton::getInstance() === $fake);

Patchwork\undo($handle);

assert(Singleton::getInstance() === $real);

# This call should have no effect, as the patch is already removed
Patchwork\undo($handle);

assert(Singleton::getInstance() === $real);

?>
===DONE===

--EXPECT--
===DONE===
