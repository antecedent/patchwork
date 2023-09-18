--TEST--
https://github.com/antecedent/patchwork/issues/126

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/RedefinitionOfNewWithUse.php";

?>
===DONE===

--EXPECT--
===DONE===
