--TEST--
Attribute declared in anonymous class

--SKIPIF--
<?php version_compare(PHP_VERSION, "8.0", ">=")
      or die("skip because attributes are only available since PHP 8.0") ?>

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/AnonymousClassAttribute.php";

?>
===DONE===

--EXPECT--
===DONE===
