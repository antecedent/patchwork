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

# Preserve behavior of call_user_func etc.
class SelfCaller
{
    static function first($x)
    {
        return call_user_func('self::second', $x);
    }

    static function second($x)
    {
        return $x * 2;
    }
}

assert(SelfCaller::first(21) === 42);


# Dynamic calls
$function = 'str' . 'len';
p\redefine('strlen', p\always('?!'));
assert($function('test') === '?!');

# Leading backslashes
$array = [2, 1, 3];
p\redefine('sort', function(&$array) {
    $array = ['original' => [2, 1, 3], 'sorted' => [1, 2, 3]];
});
\sort($array);
assert(isset($array['original']));

# Aliases
eval('use function strtolower as toLower; assert(toLower("X") === "X, but in lowercase");');

p\restoreAll();

echo 'END', PHP_EOL;
