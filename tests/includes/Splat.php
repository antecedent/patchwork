<?php

use function Patchwork\redefine, Patchwork\relay;

$arrays = [];

$arrays[] = ['foo', 'bar', 'baz'];
$arrays[] = [':', ':', ':'];
$arrays[] = [1, 2, 3];

$concatenate = function(...$args) {
    return join('', $args);
};

$counter = 0;

redefine('array_map', function() use (&$counter) {
    $counter++;
    return relay();
});

$map = 'array_map';

$result = $map($concatenate, ...$arrays);

assert($result == ['foo:1', 'bar:2', 'baz:3']);

assert($counter === 1);
