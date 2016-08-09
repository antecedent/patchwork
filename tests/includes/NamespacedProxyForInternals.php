<?php

namespace SpaceForNames;

use Patchwork as p;

echo 'BEGIN', PHP_EOL;

function time()
{
    return 'local-scoped time()';
}

function strtolower($str)
{
    return sprintf('local-scoped strtolower(%s)', $str);
}

p\redefine('time', p\always(1));
p\redefine('strtolower', p\always('*'));

assert(time() === 'local-scoped time()');
assert(\time() === 1);
assert(strtolower('foo') === 'local-scoped strtolower(foo)');
assert(\strtolower('foo') === '*');

p\restoreAll();

echo 'END', PHP_EOL;
