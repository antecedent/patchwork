--TEST--
Applying multiple patches to the same function

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\replace("getInteger", function() {
    print("Patch #1\n");
    return 1;
});

Patchwork\replace("getInteger", function() {
    print("Patch #2\n");
    return 2;
});

Patchwork\replace("getInteger", function() {
    print("Patch #3\n");
    Patchwork\fallBack();
});

echo "Calling getInteger()\n";

assert(getInteger() === 2);

Patchwork\undoAll();

assert(getInteger() === 0);

?>
===DONE===

--EXPECT--
Calling getInteger()
Patch #3
Patch #2
===DONE===
