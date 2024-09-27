--TEST--
https://github.com/antecedent/patchwork/issues/148

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/LanguageConstructParenthesizationBug.php";

?>
===DONE===

--EXPECT--
We are in a T_ENCAPSED_AND_WHITESPACE, and not in a bar.
===DONE===
