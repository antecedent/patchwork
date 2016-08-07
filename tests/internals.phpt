--TEST--
Generator support is currently excluded: https://github.com/antecedent/patchwork/issues/15

--SKIPIF--
<?php !defined('HHVM_VERSION')
      or die('skip because the redefinition of internals is not yet implemented for HHVM') ?>

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

$_SERVER['PHP_SELF'] = __FILE__;

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/ProxyForInternals.php";

Patchwork\redefine('str_replace', function($search, $replacement, $subject) {
    return sprintf('[%s -> %s | %s]', $search, $replacement, $subject);
});

callInternals();

?>
===DONE===

--EXPECT--
[foo -> bar | foobar]
===DONE===
