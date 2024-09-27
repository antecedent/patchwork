--TEST--
Leaving a patch without yielding a result

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\replace("getInteger", function() {
    Patchwork\fallBack();
    echo "This should not be printed\n";
});

assert(getInteger() === 0);

Patchwork\replace("getInteger", Patchwork\always(42));

assert(getInteger() === 42);

?>
===DONE===

--EXPECT--
===DONE===
