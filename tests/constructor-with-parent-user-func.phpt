--TEST--
Compatibility with call_user_func calling parent constructor.

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/ConstructorWithParentUserFunc.php";

$instance = new BarObject();
assert($instance->attribute === "Initialized");

?>
===DONE===

--EXPECT--
===DONE===
