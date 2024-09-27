--TEST--
https://github.com/antecedent/patchwork/issues/54, https://github.com/antecedent/patchwork/issues/66

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/RedefinitionOfNew.php";

?>
===DONE===

--EXPECT--
===DONE===
