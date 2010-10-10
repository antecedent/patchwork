--TEST--
Using Patchwork\dispatchNext() to re-dispatch magic calls

--FILE--
<?php

use Patchwork as p;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/MagicObject.php";

$magic = new MagicObject;

p\filter(array($magic, 'getFoo'), p\returnValue('foo'));
p\filter(array($magic, 'getBar'), p\returnValue('bar'));

$fwd = p\filter(array($magic, '__call'), p\dispatchNext());

echo 'getFoo: ', $magic->getFoo(), PHP_EOL;
echo 'getBar: ', $magic->getBar(), PHP_EOL;

?>

--EXPECT--
getFoo: foo
getBar: bar