--TEST--
Basic attachment and dismissal of a filter

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/foo/foo.php";

echo "BEFORE ATTACHMENT", PHP_EOL;
sayFoo();

$handle = Patchwork\filter("sayFoo", function() {
    echo "Filtered!", PHP_EOL;
});

echo "AFTER ATTACHMENT", PHP_EOL;
sayFoo();

Patchwork\dismiss($handle);

echo "AFTER DISMISSAL", PHP_EOL;
sayFoo();

?>

--EXPECT--
BEFORE ATTACHMENT
foo
AFTER ATTACHMENT
Filtered!
foo
AFTER DISMISSAL
foo
