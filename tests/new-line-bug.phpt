--TEST--
https://github.com/antecedent/patchwork/issues/94

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/includes/NewLineBug/index.php";

?>
===DONE===

--EXPECT--
1999-12-31

===DONE===
