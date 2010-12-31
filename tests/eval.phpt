--TEST--
Preprocessing of eval'd code

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

evaluate('
    function evalPreprocessingWorks()
    {
        return false;
    }
');

Patchwork\replace("evalPreprocessingWorks", function() {
    return true;
});

assert(evalPreprocessingWorks());

?>
===DONE===

--EXPECT--
===DONE===
