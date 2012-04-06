--TEST--
Leaving a patch without yielding a result

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\replace("getInteger", function() {
    Patchwork\pass();
    echo "This should not be printed\n";
});

assert(getInteger() === 0);

Patchwork\replace("getInteger", function() {
    return 42;
});

assert(getInteger() === 42);

?>
===DONE===

--EXPECT--
===DONE===
