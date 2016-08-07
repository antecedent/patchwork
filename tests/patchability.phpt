--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";

function functionThatIsNotPreprocessed()
{
}

assert(Patchwork\hasMissed('functionThatIsNotPreprocessed'));

if (!Patchwork\Utils\runningOnHHVM()) {
	expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
	    Patchwork\replace("functionThatIsNotPreprocessed", function() {});
	});
}

Patchwork\replace("functionThatIsNotDefined", function() {}, Patchwork\WARN_IF_NEVER_DEFINED);

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

Patchwork\undo(Patchwork\replace('anotherUndefinedFunction', function() {}, Patchwork\WARN_IF_NEVER_DEFINED));

# Should raise no errors
Patchwork\replace('yetAnotherUndefinedFunction', function() {});

?>
===DONE===

--EXPECTF--
Warning: anotherUndefinedFunction() was never defined during the lifetime of its redefinition in %s on line %d
===DONE===


Warning: functionThatIsNotDefined() was never defined during the lifetime of its redefinition in %s on line %d
