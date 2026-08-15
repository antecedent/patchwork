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

redefine('NamedObject::__destruct', function () {
   return __CLASS__; 
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
