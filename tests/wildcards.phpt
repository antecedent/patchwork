--TEST--
Wildcards: redefine('*', 'catchAll') etc.

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . '/../Patchwork.php';
require __DIR__ . '/includes/Functions.php';

Patchwork\redefine('*', function() {
    return 'Whammy!';
});

assert(getInteger() === 'Whammy!');
assert(getClosure() === 'Whammy!');

require __DIR__ . '/includes/NamespacedFunctions.php';

Patchwork\CallRerouting\deployQueue();

assert(Foo\identify() === 'Whammy!');

?>
===DONE===

--EXPECT--
===DONE===
