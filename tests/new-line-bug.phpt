--TEST--
https://github.com/antecedent/patchwork/issues/94

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

date_default_timezone_set('UTC');

require __DIR__ . "/includes/NewLineBug/index.php";

?>
===DONE===

--EXPECT--
1999-12-31

===DONE===
