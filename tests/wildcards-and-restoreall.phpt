--TEST--
https://github.com/antecedent/patchwork/issues/33

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . '/../Patchwork.php';
require __DIR__ . '/includes/Inheritance.php';

Patchwork\redefine('BarObject::*', function() {
    return 'redefined';
});

$bar = new BarObject;

assert($bar->getFoo() === 'redefined');
assert($bar->getBar() === 'redefined');

Patchwork\restoreAll();

assert($bar->getFoo() === 'foo');
assert($bar->getBar() === 'bar');

?>
===DONE===

--EXPECT--
===DONE===
