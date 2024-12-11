--TEST--
Test getCachedPath function (https://github.com/antecedent/patchwork/pull/170)

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";

use Patchwork\CodeManipulation\Stream;

$cachePath = str_replace('\\', '/', __DIR__ . '/cache');
Patchwork\CodeManipulation\State::$cacheIndexFile = null;
Patchwork\Config\State::$cachePath = $cachePath;

if ( ! is_dir($cachePath)) {
    mkdir($cachePath);
} else {
    echo "The cache directory should not exist in advance.\n";
}

// The expected path depends on the current implementation of getCachedPath().
$file = 'test-file.php';
$hash = md5($file);

$expectedPath = $cachePath . '/' . $hash . '.php';

// First pass - index.csv file does not exist.
$actualPath = \Patchwork\CodeManipulation\getCachedPath($file);

echo $actualPath === $expectedPath ? 'PASS' : 'FAIL';
echo "\n";

// Second pass - index.csv file exists.
$actualPath = \Patchwork\CodeManipulation\getCachedPath($file);

echo $actualPath === $expectedPath ? 'PASS' : 'FAIL';
echo "\n";

// Third pass - index.csv file exists, different file being passed.
// This test would hit a PHP 8.4 deprecation without the fix from PR #170.

// Reset the state to ensure we hit the code which would cause the deprecation
// notice without the fix from #170.
Patchwork\CodeManipulation\State::$cacheIndexFile = null;
Patchwork\Config\State::$cachePath = $cachePath;

$file = 'another-file.php';
$hash = md5($file);

$expectedPath = $cachePath . '/' . $hash . '.php';

$actualPath = \Patchwork\CodeManipulation\getCachedPath($file);

echo $actualPath === $expectedPath ? 'PASS' : 'FAIL';
echo "\n";
?>
===DONE===

--CLEAN--
<?php

unlink(__DIR__ . '/cache/index.csv');
rmdir(__DIR__ . '/cache');

?>
--EXPECT--
PASS
PASS
PASS
===DONE===
