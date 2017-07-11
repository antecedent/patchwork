--TEST--
https://github.com/antecedent/patchwork/issues/56

--SKIPIF--
<?php

version_compare(PHP_VERSION, "5.6", ">=")
    or die("skip because this bug only occurs in PHP 5.6 and up");

!defined('HHVM_VERSION')
    or die('skip because the redefinition of internals is not yet implemented for HHVM');

?>

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Splat.php";

?>
===DONE===

--EXPECT--
===DONE===
