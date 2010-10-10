--TEST--
Returning references from short-circuited calls

--FILE--
<?php

use Patchwork\Call;
use Patchwork\Reference;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";

$array = array(
    "first"  => "foo",
    "second" => "bar"
);

Patchwork\filter('Functions\getElement', function(Call $call) use (&$array) {
    list($key) = $call->args;
    $call->complete(new Reference($array[$key]));
});

$second = &Functions\getElement("second");

$second = "not bar anymore";

foreach ($array as $offset => $value) {
    echo "$offset: $value", PHP_EOL;
}

?>

--EXPECT--
first: foo
second: not bar anymore