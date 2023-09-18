--TEST--
Not allowing to patch functions that are not defined or not preprocessed

--FILE--
<?php

ini_set('zend.assertions', 1);
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
	    Patchwork\replace("functionThatIsNotPreprocessed", Patchwork\always(null));
	});
}

$h = Patchwork\replace("functionThatIsNotDefined", Patchwork\always(null));
Patchwork\assertEventuallyDefined($h);

if (!Patchwork\Utils\runningOnHHVM()) {
	expectException('Patchwork\Exceptions\DefinedTooEarly', function() {
	    Patchwork\replaceLater("functionThatIsNotPreprocessed", Patchwork\always(null));
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
	    Patchwork\replace("Singleton::getInstance", Patchwork\always(null));
	});
}

$h = Patchwork\replace('anotherUndefinedFunction', Patchwork\always(null));
Patchwork\assertEventuallyDefined($h);
Patchwork\undo($h);

# Should raise no errors
Patchwork\replace('yetAnotherUndefinedFunction', Patchwork\always(null));

?>
===DONE===

--EXPECTF--
Warning: anotherUndefinedFunction() was never defined during the lifetime of its redefinition in %s on line %d
===DONE===


Warning: functionThatIsNotDefined() was never defined during the lifetime of its redefinition in %s on line %d
