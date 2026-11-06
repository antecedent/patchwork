--TEST--
Calling a static function from a non-static function via call_user_func when internals are redefined

--FILE--
<?php

use Patchwork as p;

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/StaticCallWithUserFunc.php";

assert((new BarObject)->callFoo() === 'original');

p\redefine("FooObject::staticFunction", function() {
	assert(p\relay() === 'original');
	return 'replaced';
});

assert((new BarObject)->callFoo() === 'replaced');

?>
===DONE===

--EXPECT--
===DONE===
