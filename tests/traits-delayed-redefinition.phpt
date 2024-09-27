--TEST--
Patching methods imported from traits using Patchwork\replaceLater (https://github.com/antecedent/patchwork/issues/5)

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

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
