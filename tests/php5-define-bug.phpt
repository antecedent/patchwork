--TEST--
https://github.com/antecedent/patchwork/issues/75

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Php5DefineBug.php";

?>
===DONE===

--EXPECT--
===DONE===
