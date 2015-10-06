--TEST--
Patching methods imported from traits using Patchwork\replaceLater (https://github.com/antecedent/patchwork/issues/5)

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.4", ">=")
      or die("skip because traits are not supported in this version of PHP") ?>

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

Patchwork\replaceLater("FooTrait::speak", function() {
    return "spam";
});

Patchwork\replaceLater("Babbler::speak", function() {
    return "eggs";
});

Patchwork\replaceLater("Babbler::sayBar", function() {
    return "bacon";
});

require __DIR__ . "/includes/TraitsWithAssertions.php";

?>
===DONE===

--EXPECT--
===DONE===
