--TEST--
Inheriting method patches + delayed redefinition (suspected HHVM issue)

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

Patchwork\replace("BarObject::getFoo", function() {
    return "foo (patched)";
});

Patchwork\replace("BarObject::getBar", function() {
    return "bar (patched)";
});

require __DIR__ . "/includes/InheritanceWithAssertions.php";

Patchwork\undoAll();

?>
===DONE===

--EXPECT--
===DONE===
