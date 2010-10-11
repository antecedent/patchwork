--TEST--
Preprocessing of eval()'d code

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Functions\evaluate('
    function evalPreprocessingWorks()
    {
        return false;
    }
');

Patchwork\filter('evalPreprocessingWorks', Patchwork\returnValue(true));

var_export(evalPreprocessingWorks());

?>

--EXPECT--
true