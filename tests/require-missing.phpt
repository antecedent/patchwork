--TEST--
Include and require of nonexistent files

--SKIPIF--
<?php
// PHP 8.0 changed require failing from a fatal error to a thrown exception. That's too much of a difference
// to handle easily in EXPECTF, easier to just copy the file.
version_compare(PHP_VERSION, "8.0", ">=") or die("skip PHP 8+ version of the test in PHP <8");

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

echo "Including missing file...\n";
include __DIR__ . "/includes/does-not-exist.php";
echo "Good, it did not throw/exit.\n\n";

echo "Requiring missing file...\n";
require __DIR__ . "/includes/does-not-exist.php";
echo "It did not throw/exit. This is wrong.\n";

?>
===DONE===

--EXPECTF--
Including missing file...

Warning: file_get_contents(%s/tests/includes/does-not-exist.php): Failed to open stream: No such file or directory in %s/src/CodeManipulation.php on line %d

Warning: include(%s/tests/includes/does-not-exist.php): Failed to open stream: "Patchwork\CodeManipulation\Stream::stream_open" call failed in Standard input code on line 10

Warning: include(): Failed opening '%s/tests/includes/does-not-exist.php' for inclusion (include_path='%s') in Standard input code on line 10
Good, it did not throw/exit.

Requiring missing file...

Warning: file_get_contents(%s/tests/includes/does-not-exist.php): Failed to open stream: No such file or directory in %s/src/CodeManipulation.php on line %d

Warning: require(%s/tests/includes/does-not-exist.php): Failed to open stream: "Patchwork\CodeManipulation\Stream::stream_open" call failed in Standard input code on line 14

Fatal error: Uncaught Error: Failed opening required '%s/tests/includes/does-not-exist.php' (include_path='%s') in Standard input code:14
Stack trace:
#0 {main}
  thrown in Standard input code on line 14
