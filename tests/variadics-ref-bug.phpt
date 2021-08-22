--TEST--
https://github.com/antecedent/patchwork/issues/115

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.6", ">=")
      or die("skip because variadics are not available until PHP 5.6") ?>

--FILE--
<?php
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);
require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/VariadicsRefBug.php";

?>
===DONE===
--EXPECT--
a=2 b=4 c=5
a=14 b=25 c=26
===DONE===