--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";

function functionThatIsNotPreprocessed()
{
}

try {
    Patchwork\replace("functionThatIsNotDefined", function() {});
    assert(false);
} catch (Patchwork\Exceptions\NotDefined $e) {
    assert(true);
}

try {
    Patchwork\replace("functionThatIsNotPreprocessed", function() {});
    assert(false);
} catch (Patchwork\Exceptions\NotPreprocessed $e) {
    assert(true);
}

try {
    Patchwork\replace("str_replace", function() {});
    assert(false);
} catch (Patchwork\Exceptions\NotPreprocessed $e) {
    assert(true);
}

?>
===DONE===

--EXPECT--
===DONE===