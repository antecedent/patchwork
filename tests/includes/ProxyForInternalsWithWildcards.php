<?php

namespace AnotherSpaceForNames;

use Patchwork as p;

echo 'BEGIN', PHP_EOL;

p\redefine('str*', p\always('redefined'));

assert(strlen('abc') === 'redefined');
assert(strtolower('ABC') === 'redefined');
assert(time() > 1000);

p\restoreAll();

echo 'END', PHP_EOL;
