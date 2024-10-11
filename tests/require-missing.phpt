--TEST--
Include and require of nonexistent files

--SKIPIF--
<?php
// PHP 8.0 changed require failing from a fatal error to a thrown exception. That's too much of a difference
// to handle easily in EXPECTF, easier to just copy the file.
if (!version_compare(PHP_VERSION, "8.0", ">=")) echo "skip PHP 8+ version of the test in PHP <8";
--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

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

Warning: fopen(%s/includes/does-not-exist.php): Failed to open stream: No such file or directory in %s%esrc%eCodeManipulation%eStream.php on line %d

Warning: include(%s/includes/does-not-exist.php): Failed to open stream: "Patchwork\CodeManipulation\Stream::stream_open" call failed in %s on line 9

Warning: include(): Failed opening '%s/includes/does-not-exist.php' for inclusion (include_path='%s') in %s on line 9
Good, it did not throw/exit.

Requiring missing file...

Warning: fopen(%s/includes/does-not-exist.php): Failed to open stream: No such file or directory in %s%esrc%eCodeManipulation%eStream.php on line %d

Warning: require(%s/includes/does-not-exist.php): Failed to open stream: "Patchwork\CodeManipulation\Stream::stream_open" call failed in %s on line 13

Fatal error: Uncaught Error: Failed opening required '%s/includes/does-not-exist.php' (include_path='%s') in %s:13
Stack trace:
#0 {main}
  thrown in %s on line 13
