--TEST--
https://github.com/antecedent/patchwork/issues/95

--FILE--
<?php
ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);
require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/VoidTyped.php";

$n = 0;

$countCalls = function() use (&$n) {
	$n++;
};

Patchwork\redefine('iAmVoidTyped', $countCalls);
Patchwork\redefine('iAmNotVoidTyped', $countCalls);

iAmVoidTyped();
iAmNotVoidTyped();

assert($n === 2);

$returnValue = function() {
    return 42;
};
Patchwork\redefine('iAmVoidTyped', $returnValue);
Patchwork\redefine('iAmNotVoidTyped', $returnValue);
try {
    iAmVoidTyped();
    echo "Did not throw expected \\Patchwork\\Exceptions\\NonNullToVoid exception\n";
} catch(\Patchwork\Exceptions\NonNullToVoid $ex) {
}
iAmNotVoidTyped();

?>
===DONE===

--EXPECT--
===DONE===
