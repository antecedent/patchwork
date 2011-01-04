--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";

Patchwork\Preprocessor\exclude(__DIR__ . "/includes/Singleton.php");

function functionThatIsNotPreprocessed()
{
}

expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
    Patchwork\replace("functionThatIsNotPreprocessed", function() {});
});

expectException('Patchwork\Exceptions\NotDefined', function() {
    Patchwork\replace("functionThatIsNotDefined", function() {});
});

expectException('Patchwork\Exceptions\NotUserDefined', function() {
    Patchwork\replace("str_replace", function() {});
});

expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
    Patchwork\replaceLater("functionThatIsNotPreprocessed", function() {});
});

Patchwork\replaceLater("getInteger", function() {
    return 42;
});

require __DIR__ . "/includes/Functions.php";

assert(getInteger() === 42);

require __DIR__ . "/includes/Singleton.php";

expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
    Patchwork\replace("Singleton::getInstance", function() {});
});

?>
===DONE===

--EXPECT--
===DONE===
