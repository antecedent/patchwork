--TEST--
Short-circuiting of calls

--FILE--
<?php

use Patchwork\Call;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

Patchwork\filter('Functions\getString', function(Call $c) {
    $c->complete("filtered");
});

Patchwork\filter('Functions\writeStringToArgument', function(Call $c) {
    $c->args[0] = "filtered";
    $c->complete(false);
});

echo "getString: ",
     Functions\getString(), PHP_EOL;

echo "writeStringToArgument: ",
     var_export(Functions\writeStringToArgument($str), true), " ($str)", PHP_EOL;

?>

--EXPECT--
getString: filtered
writeStringToArgument: false (filtered)