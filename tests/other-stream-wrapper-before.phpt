--TEST--
https://github.com/antecedent/patchwork/issues/138
Case 2/2: the other stream wrapper is registered BEFORE importing Patchwork.

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/includes/StreamWrapperForTesting.php";
require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\redefine('getInteger', function() {
    return 1;
});

assert(getInteger() === 1);

assert(in_array('Functions.php', StreamWrapperForTesting::$pathsOpened));

?>
===DONE===

--EXPECT--
===DONE===
