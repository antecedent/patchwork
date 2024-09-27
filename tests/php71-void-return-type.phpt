--TEST--
https://github.com/antecedent/patchwork/issues/64

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Php71VoidReturnType.php";

?>
===DONE===

--EXPECT--
===DONE===
