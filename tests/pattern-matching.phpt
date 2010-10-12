--TEST--
Call pattern matching using filter chains

--FILE--
<?php

use Patchwork as p;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Cache.php";

# Handle fetch("first")
p\filter("Cache::fetch", p\chain(
    p\requireArgs(array("first")),
    p\assignArgs(array(1 => "foo")),
    p\returnValue(true)
));

# Handle fetch("second")
p\filter("Cache::fetch", p\chain(
    p\requireArgs(array("second")),
    p\assignArgs(array(1 => "bar")),
    p\returnValue(true)
));

# Handle fetch("inexistent")
p\filter("Cache::fetch", p\chain(
    p\requireArgs(array("inexistent")),
    p\returnValue(false)
));

# Disallow any other argument lists
p\filter("Cache::fetch", p\assertCompleted());

assert(Cache::fetch("first", $result) === true);
assert($result === "foo");

assert(Cache::fetch("second", $result) === true);
assert($result === "bar");

assert(Cache::fetch("inexistent", $result) === false);

try {
    Cache::fetch("unexpected", $result);
} catch (p\Exceptions\UnexpectedUncompletedCall $e) {
    echo "OK";
}

?>

--EXPECT--
OK