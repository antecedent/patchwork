--TEST--
Wildcards: redefine('*', 'catchAll') etc.

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . '/../Patchwork.php';
require __DIR__ . '/includes/Functions.php';

Patchwork\redefine('*', function() {
    return 'Whammy!';
});

assert(getInteger() === 'Whammy!');
assert(getClosure() === 'Whammy!');

# Not yet equal in version 1.4
assert(get_declared_classes() !== 'Whammy!');

require __DIR__ . '/includes/NamespacedFunctions.php';

Patchwork\CallRerouting\deployQueue();

assert(Foo\identify() === 'Whammy!');

?>
===DONE===

--EXPECT--
===DONE===
