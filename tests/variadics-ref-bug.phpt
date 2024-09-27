--TEST--
https://github.com/antecedent/patchwork/issues/115

--FILE--
<?php
ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);
require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/VariadicsRefBug.php";

?>
===DONE===
--EXPECT--
a=2 b=4 c=5
a=14 b=25 c=26
===DONE===