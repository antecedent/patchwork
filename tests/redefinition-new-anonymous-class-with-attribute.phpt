--TEST--
Redefinition of new in anonymous class with attribute

--SKIPIF--
<?php
if (!version_compare(PHP_VERSION, "8.0", ">=")) {
    echo "skip because attributes are only available since PHP 8.0";
}
--FILE--
<?php

error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/AnonymousClassAttribute.php";

?>
===DONE===

--EXPECT--
===DONE===
