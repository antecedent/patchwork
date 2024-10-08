--TEST--
https://github.com/antecedent/patchwork/issues/114

--FILE--
<?php
ini_set('zend.assertions', 1);
error_reporting(E_ALL);
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