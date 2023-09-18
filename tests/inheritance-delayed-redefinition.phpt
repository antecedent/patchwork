--TEST--
Inheriting method patches + delayed redefinition (suspected HHVM issue)

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

Patchwork\replace("BarObject::getFoo", function() {
    return "foo (patched)";
});

Patchwork\replace("BarObject::getBar", function() {
    return "bar (patched)";
});

require __DIR__ . "/includes/InheritanceWithAssertions.php";

?>
===DONE===

--EXPECT--
===DONE===
