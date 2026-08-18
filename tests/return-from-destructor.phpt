--TEST--
Return from destructor / antecedent/patchwork#213

--FILE--
<?php

use function Patchwork\redefine;

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

$classes = [];

redefine('*::__destruct', function () use (&$classes) {
    $class = get_class($this);
    $classes[] = $class;
    return $class;
});

echo "Named class:\n";

try {
    new NamedObject('foo');
} catch (Exception $e) {
    echo get_class($e), "\n";
}

echo "Anonymous subclass:\n";

try {
    NamedObject::createAnonymousSubclassInstance();
} catch (Exception $e) {
    echo get_class($e), "\n";
}

echo "(has its own destructor that is not redefinable using Patchwork)\n";

assert($classes === ['NamedObject']);

?>
===DONE===

--EXPECT--
Named class:
Patchwork\Exceptions\NonNullToVoid
Anonymous subclass:
(has its own destructor that is not redefinable using Patchwork)
===DONE===
