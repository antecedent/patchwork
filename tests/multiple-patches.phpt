--TEST--
Applying multiple patches to the same function

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\patch("getInteger", function() {
    echo "Patch #1\n";
    return 1;
});

Patchwork\patch("getInteger", function() {
    echo "Patch #2\n";
    Patchwork\escape();
});

Patchwork\patch("getInteger", function() {
    echo "Patch #3\n";
    return 2;
});

echo "Calling getInteger()\n";

assert(getInteger() === 2);
 
?>
===DONE===

--EXPECT--
Calling getInteger()
Patch #1
Patch #2
Patch #3
===DONE===