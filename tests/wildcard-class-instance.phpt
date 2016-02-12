--TEST--
Wildcards: redefine([$instance, '*'], 'catchAll') etc.

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . '/../Patchwork.php';
require __DIR__ . '/includes/Functions.php';

require __DIR__ . '/includes/Inheritance.php';

$bar = new BarObject;

Patchwork\redefine([$bar, '*'], function() {
    return 'Whammy!';
});

assert($bar->getBar() === 'Whammy!');
assert($bar->getFoo() === 'Whammy!');

?>
===DONE===

--EXPECT--
===DONE===
