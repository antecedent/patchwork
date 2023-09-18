--TEST--
Redefinition of internal functions

--SKIPIF--
<?php !defined('HHVM_VERSION')
      or die('skip because the redefinition of internals is not yet implemented for HHVM') ?>

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";

require __DIR__ . "/includes/ProxyForInternals.php";
require __DIR__ . "/includes/NamespacedProxyForInternals.php";
require __DIR__ . "/includes/ProxyForInternalsWithWildcards.php"

?>
===DONE===

--EXPECT--
BEGIN
END
BEGIN
END
BEGIN
END
===DONE===
