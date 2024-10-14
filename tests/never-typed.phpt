--TEST--
https://github.com/antecedent/patchwork/issues/140

--SKIPIF--
<?php
if (!version_compare(PHP_VERSION, "8.1", ">=")) {
    echo "skip because this bug only occurs in PHP 8.1";
}
--FILE--
<?php
ini_set('zend.assertions', 1);
error_reporting(E_ALL);
require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NeverTyped.php";

class NeverTypedTestException extends Exception {
}

$n = 0;

$countCalls = function() use (&$n) {
	$n++;
};
$countCallsAndThrow = function() use (&$n) {
	$n++;
        throw new NeverTypedTestException;
};

Patchwork\redefine('iAmNeverTyped', $countCallsAndThrow);
Patchwork\redefine('iAmNotNeverTyped', $countCalls);

try {
    iAmNeverTyped();
    echo "Did not throw expected NeverTypedTestException exception\n";
} catch ( NeverTypedTestException $ex ) {
}
iAmNotNeverTyped();

assert($n === 2);

Patchwork\redefine('iAmNeverTyped', $countCalls);
try {
    iAmNeverTyped();
    echo "Did not throw expected \\Patchwork\\Exceptions\\ReturnFromNever exception\n";
} catch ( \Patchwork\Exceptions\ReturnFromNever $ex ) {
}

?>
===DONE===

--EXPECT--
===DONE===
