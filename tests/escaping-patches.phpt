--TEST--
Leaving a patch without yielding a result

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\replace("getInteger", function() {
    Patchwork\escape();
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
