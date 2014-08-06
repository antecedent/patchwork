--TEST--
Applying multiple patches to the same function

--FILE--
<?php

error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\replace("getInteger", function() {
    echo "Patch #1\n";
    return 1;
});

Patchwork\replace("getInteger", function() {
    echo "Patch #2\n";
    return 2;
});

Patchwork\replace("getInteger", function() {
    echo "Patch #3\n";
    Patchwork\pass();
});

echo "Calling getInteger()\n";

assert(getInteger() === 2);

Patchwork\undoAll();

assert(getInteger() === 0);

?>
===DONE===

--EXPECT--
Calling getInteger()
Patch #1
Patch #2
Patch #3
===DONE===
