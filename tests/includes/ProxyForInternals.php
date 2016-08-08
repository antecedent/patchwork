<?php

use Patchwork as p;

echo 'BEGIN', PHP_EOL;

# Direct calls
assert(time() > 10);
p\redefine('time', p\always(10));
assert(time() === 10);
p\restoreAll();
assert(time() > 10);

# Indirect calls
p\redefine('strtolower', function($str) {
    return sprintf('%s, but in lowercase', $str);
});
assert(array_map('strtolower', ['Foo', 'BAR', 'baz']) === [
    'Foo, but in lowercase',
    'BAR, but in lowercase',
    'baz, but in lowercase',
]);

# Dynamic calls
$function = 'str' . 'len';
p\redefine('strlen', p\always('?!'));
assert($function('test') === '?!');

# Dynamic calls: syntax only available since ASTs
# if (version_compare(PHP_VERSION, "7.0", ">=")) {
#     eval('assert(("str" . "len")("value") === "?!");');
# }

# Leading backslashes
$array = [2, 1, 3];
p\redefine('sort', function(&$array) {
    $array = ['original' => [2, 1, 3], 'sorted' => [1, 2, 3]];
});
\sort($array);
assert(isset($array['original']));

# Aliases
if (version_compare(PHP_VERSION, "7.0", ">=")) {
    eval('use function strtolower as toLower; assert(toLower("X") === "X, but in lowercase");');
}

p\restoreAll();

echo 'END', PHP_EOL;
