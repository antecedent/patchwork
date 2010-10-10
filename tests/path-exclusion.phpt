--TEST--
Excluding files and directories from preprocessing

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";

# Only baz.php should remain unexcluded
Patchwork\exclude(__DIR__ . "/includes/foo");
Patchwork\exclude(__DIR__ . "/includes/bar-and-baz/bar.php");

require __DIR__ . "/includes/foo/foo.php";
require __DIR__ . "/includes/bar-and-baz/bar.php";
require __DIR__ . "/includes/bar-and-baz/baz.php";

$filter = function($call) {
    echo "[censored]", PHP_EOL;
    $call->complete();
};

# Only sayBaz should be actually filtered
foreach (array("sayFoo", "sayBar", "sayBaz") as $function) {
    Patchwork\filter($function, $filter);
    echo $function, " says ", $function();
}

?>

--EXPECT--
sayFoo says foo
sayBar says bar
sayBaz says [censored]