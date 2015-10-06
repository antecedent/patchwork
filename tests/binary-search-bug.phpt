--TEST--
Binary search bug: https://github.com/antecedent/patchwork/issues/16

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Interface.php";

?>
===DONE===

--EXPECT--
===DONE===
