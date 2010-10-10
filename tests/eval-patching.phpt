--TEST--
Patching of eval()'d code

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Functions\evaluate('
    function evalPatchingWorks()
    {
        return false;
    }
');

Patchwork\filter('evalPatchingWorks', Patchwork\returnValue(true));

var_export(evalPatchingWorks());

?>

--EXPECT--
true