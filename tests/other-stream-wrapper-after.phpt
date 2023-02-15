--TEST--
https://github.com/antecedent/patchwork/issues/138
Case 1/2: the other stream wrapper is registered AFTER importing Patchwork.

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/includes/StreamWrapperForTesting.php";

require __DIR__ . "/../Patchwork.php";

stream_wrapper_unregister('file');
stream_wrapper_register('file', 'StreamWrapperForTesting');

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
