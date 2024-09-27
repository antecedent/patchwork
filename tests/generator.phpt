--TEST--
Generator support is currently excluded: https://github.com/antecedent/patchwork/issues/15

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Generator.php";

?>
===DONE===

--EXPECT--
===DONE===
