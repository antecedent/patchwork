--TEST--
https://github.com/antecedent/patchwork/issues/33

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . '/../Patchwork.php';
require __DIR__ . '/includes/Inheritance.php';

var_dump(get_declared_classes());

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
