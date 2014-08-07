--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";

Patchwork\Preprocessor\exclude(__DIR__ . "/includes/Singleton.php");

function functionThatIsNotPreprocessed()
{
}

if (!Patchwork\Utils\runningOnHHVM()) {
	expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
	    Patchwork\replace("functionThatIsNotPreprocessed", function() {});
	});
}

Patchwork\replace("functionThatIsNotDefined", function() {});

expectException('Patchwork\Exceptions\NotUserDefined', function() {
    Patchwork\replace("str_replace", function() {});
});

if (!Patchwork\Utils\runningOnHHVM()) {
	expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
	    Patchwork\replaceLater("functionThatIsNotPreprocessed", function() {});
	});
}

Patchwork\replaceLater("getInteger", function() {
    return 42;
});

require __DIR__ . "/includes/Functions.php";

assert(getInteger() === 42);

require __DIR__ . "/includes/Singleton.php";

if (!Patchwork\Utils\runningOnHHVM()) {
	expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
	    Patchwork\replace("Singleton::getInstance", function() {});
	});
}

Patchwork\undo(Patchwork\replace('anotherUndefinedFunction', function() {}));

?>
===DONE===

--EXPECTF--
Warning: anotherUndefinedFunction was never defined during the lifetime of its redefinition in %s on line %d
===DONE===


Warning: functionThatIsNotDefined was never defined during the lifetime of its redefinition in %s on line %d
