--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";

function functionThatIsNotPreprocessed()
{
}

expectException('Patchwork\Exceptions\NotPreprocessed', function() {
    Patchwork\replace("functionThatIsNotPreprocessed", function() {});
});

expectException('Patchwork\Exceptions\NotDefined', function() {
    Patchwork\replace("functionThatIsNotDefined", function() {});
});

expectException('Patchwork\Exceptions\NotPreprocessed', function() {
    Patchwork\replace("str_replace", function() {});
});

expectException('Patchwork\Exceptions\NotPreprocessed', function() {
    Patchwork\replaceLater("functionThatIsNotPreprocessed", function() {});
});

Patchwork\replaceLater("getInteger", function() {
    return 42;
});

require __DIR__ . "/includes/Functions.php";

assert(getInteger() === 42);

?>
===DONE===

--EXPECT--
===DONE===
