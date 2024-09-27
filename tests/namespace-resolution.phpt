--TEST--
https://github.com/antecedent/patchwork/issues/70

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/NamespaceResolution.php";

?>
===DONE===

--EXPECT--
===DONE===
