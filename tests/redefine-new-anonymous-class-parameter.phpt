--TEST--
https://github.com/antecedent/patchwork/issues/127

--SKIPIF--
<?php version_compare(PHP_VERSION, "7.0", ">=")
      or die("skip because anonymous classes are only supported since PHP 7") ?>

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/RedefinitionOfNewAsAnonymousClassParameter.php";

?>
===DONE===

--EXPECT--
===DONE===
