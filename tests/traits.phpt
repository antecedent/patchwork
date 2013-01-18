--TEST--
Patching methods imported from traits (https://github.com/antecedent/patchwork/issues/5)

--SKIPIF--
<?php version_compare(PHP_VERSION, "5.4", ">=")
      or die("skip because traits are not supported in this version of PHP") ?>

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../patchwork.phar";
require __DIR__ . "/includes/Traits.php";

# Initial behavior
assert(FooTrait::speak() === "foo");
assert(BarTrait::speak() === "bar");
assert(Babbler::sayFoo() === "foo");
assert(Babbler::sayBar() === "bar");
assert(Babbler::speak() === "foobar");

Patchwork\replace("BarTrait::speak", function() {
    return "spam";
});

# Replacement #1
assert(BarTrait::speak() === "spam");

# No change expected
assert(FooTrait::speak() === "foo");
assert(Babbler::sayFoo() === "foo");
assert(Babbler::sayBar() === "bar");
assert(Babbler::speak() === "foobar");

Patchwork\replace("Babbler::speak", function() {
    return "eggs";
});

# Replacement #2
assert(Babbler::speak() === "eggs");

# Replacement #1
assert(BarTrait::speak() === "spam");

# No change expected
assert(FooTrait::speak() === "foo");
assert(Babbler::sayFoo() === "foo");
assert(Babbler::sayBar() === "bar");

Patchwork\replace("Babbler::sayFoo", function() {
    return "bacon";
});

# Replacement #3
assert(Babbler::sayFoo() === "bacon");

# Replacement #2
assert(Babbler::speak() === "eggs");

# Replacement #1
assert(BarTrait::speak() === "spam");

# No change expected
assert(FooTrait::speak() === "foo");
assert(Babbler::sayBar() === "bar");

?>
===DONE===

--EXPECT--
===DONE===
