--TEST--
https://github.com/antecedent/patchwork/issues/114

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.6", ">=")
      or die("skip because variadics are not available until PHP 5.6") ?>

--FILE--
<?php
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);
require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/VariadicsBug.php";

?>
===DONE===
--EXPECT--
$a is 1
$args are array (
  0 => 2,
  1 => 3,
)
redefined!
$a is 4
$args are array (
  0 => 5,
  1 => 6,
)
===DONE===