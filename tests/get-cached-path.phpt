--TEST--
Test getCachedPath function

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";

use Patchwork\CodeManipulation\Stream;

$cachePath = str_replace('\\', '/', __DIR__ . '/cache');
Patchwork\CodeManipulation\State::$cacheIndexFile = null;
Patchwork\Config\State::$cachePath = $cachePath;

if ( ! is_dir($cachePath)) {
    mkdir($cachePath);
}

// Test getCachedPath function
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
?>

--EXPECT--
PASS
PASS
