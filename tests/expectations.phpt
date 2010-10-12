--TEST--
Setting and checking call count expectations

--FILE--
<?php

use Patchwork as p;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Cache.php";

p\filter("Cache::store", p\chain(
	p\requireArgs(array("fail-on-call")),
	p\expectCalls(0)
));

$handle = p\filter("Cache::store", p\chain(
	p\requireArgs(array("fail-on-dismiss")),
	p\expectCalls(1)
));

p\filter("Cache::store", p\chain(
	p\requireArgs(array("fail-on-shutdown")),
	p\expectCalls(5, 10)
));

p\filter("Cache::store", p\chain(
	p\requireArgs(array("ok")),
	p\expectCalls(1, INF)
));

try {
    Cache::store("fail-on-call", "foo");
} catch (p\Exceptions\UnmetCallCountExpectation $e) {
    echo $e->getMessage(), PHP_EOL;
}

try {
	p\dismiss($handle);
} catch (p\Exceptions\UnmetCallCountExpectation $e) {
    echo $e->getMessage(), PHP_EOL;
}

foreach (range(1, 4) as $i) {
    Cache::store("ok", "foo");
}

set_error_handler(function ($level, $message) {
	echo "Error: ", $message, PHP_EOL;
});

?>
===SHUTDOWN===
--EXPECTF--
Unmet call count expectation: 0 expected, 1 received (set in %s:%d)
Unmet call count expectation: 1 expected, 0 received (set in %s:%d)
===SHUTDOWN===
Error: Unmet call count expectation: 5 to 10 expected, 0 received (set in %s:%d)