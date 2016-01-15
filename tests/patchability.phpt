--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/TestUtils.php";

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

Patchwork\undo(Patchwork\replace('anotherUndefinedFunction', function() {}));

# Should raise no errors
Patchwork\replace('yetAnotherUndefinedFunction', function() {})->silence();

?>
===DONE===

--EXPECTF--
Warning: Please import Patchwork from a point in your code where no user-defined function, class or trait is yet defined. %s() and possibly others currently violate this. in %s on line %d

Warning: anotherUndefinedFunction() was never defined during the lifetime of its redefinition in %s on line %d
===DONE===


Warning: functionThatIsNotDefined() was never defined during the lifetime of its redefinition in %s on line %d
