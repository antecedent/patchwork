--TEST--
Patching methods imported from traits (https://github.com/antecedent/patchwork/issues/5)

--SKIPIF--
<?php defined("HHVM_VERSION")
      or die("skip because not running on HHVM") ?>

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

Patchwork\replace("BarObject::getFoo", function() {
    return "foo (patched)";
});

require __DIR__ . "/includes/Inheritance.php";

?>
===DONE===

--EXPECT--
===DONE===
