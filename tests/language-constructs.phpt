--TEST--
Redefining language constructs like die(), echo, require_once etc. (https://github.com/antecedent/patchwork/issues/59)

--SKIPIF--
<?php !defined('HHVM_VERSION')
      or die('skip because the redefinition of language constructs is not yet implemented for HHVM') ?>

--FILE--
<?php

namespace Patchwork;

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";

$calls = [
    'die' => [],
    'exit' => [],
    'echo' => [],
    'print' => [],
];

function collect(array &$collection)
{
    return function() use (&$collection) {
        $collection[] = func_get_args();
    };
}

redefine('die', collect($calls['die']));
redefine('exit', collect($calls['exit']));
redefine('echo', collect($calls['echo']));
redefine('print', collect($calls['print']));

require __DIR__ . "/includes/LanguageConstructUsages.php";

assert($calls['die'] == [
    [],
    [],
    [],
]);

assert($calls['exit'] == [
    ['error'],
]);

assert($calls['echo'] == [
    ['This is a string'],
    ['This is a string', ' and this is another one'],
]);

assert($calls['print'] == [
    [404],
]);

?>
===DONE===

--EXPECT--
===DONE===
