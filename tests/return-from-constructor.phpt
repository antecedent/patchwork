--TEST--
https://github.com/antecedent/patchwork/issues/213

--FILE--
<?php

use function Patchwork\redefine;

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamedObject.php";

redefine('NamedObject::__construct', function ($name) {
   return $name; 
});

try {
    new NamedObject('foo');
} catch (Exception $e) {
    echo get_class($e), "\n";
}

?>
===DONE===

--EXPECT--
Patchwork\Exceptions\NonNullToVoid
===DONE===
