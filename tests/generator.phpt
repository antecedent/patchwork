--TEST--
Generator support is currently excluded: https://github.com/antecedent/patchwork/issues/15

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.5", ">=")
      or die("skip because generators are not supported in this version of PHP") ?>

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Generator.php";

?>
===DONE===

--EXPECT--
===DONE===
