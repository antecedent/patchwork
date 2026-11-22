--TEST--
A call to a callback-taking internal that spans lines keeps the file's line numbers

--FILE--
<?php

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";

require __DIR__ . "/includes/InternalCallbackLineNumbers.php";

echo $line, "\n";

?>
===DONE===

--EXPECT--
8
===DONE===
