--TEST--
Redefinition of internal functions

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

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
