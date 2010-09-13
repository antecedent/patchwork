--TEST--
Basic use of listeners and their dismissal

--FILE--
<?php

require __DIR__ . '/../patchwork.php';

require __DIR__ . '/includes/functions.php';

echo "1:";
print_name();

Patchwork\listen("print_name", function() {
	echo "not print_name\n";
});

echo "2:";
print_name();

echo "3:";
echo get_name() . "\n";

Patchwork\listen("get_name", function() {
	return "not get_name";
});

echo "4:";
echo get_name() . "\n";

echo "5:";
print_name();

Patchwork\dismiss("print_name");

echo "6:";
print_name();

echo "7:";
echo get_name() . "\n";

Patchwork\dismiss("get_name");

echo "8:";
echo get_name() . "\n";

?>

--EXPECT--
1:print_name
2:not print_name
3:get_name
4:not get_name
5:not print_name
6:print_name
7:not get_name
8:get_name
