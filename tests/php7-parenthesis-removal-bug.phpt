--TEST--
https://github.com/antecedent/patchwork/issues/147

--SKIPIF--
<?php version_compare(PHP_VERSION, "7.0", ">=")
      or die("skip because this bug only occurs in PHP 7+") ?>

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/ParenthesisRemovalBug.php";

?>
===DONE===

--EXPECT--
===DONE===
