--TEST--
Test url_stat implementation - https://github.com/antecedent/patchwork/issues/116

--SKIPIF--
<?php substr(PHP_OS, 0, 3) !== 'WIN'
    or die('skip because no symlinks on Windows');

--FILE--
<?php

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 1);
error_reporting(E_ALL | E_STRICT);

require __DIR__ . "/../Patchwork.php";

file_put_contents(__DIR__ . '/stream-url-stat.src.txt', str_repeat('x', 100));

// Test that copy() doesn't leave the dest file in the stat cache.
// We don't print a "before" size because in PHP 8.1 `copy()` doesn't mess with the stat cache at all,
// but we can't reproduce that behavior.
echo "\n";
file_put_contents(__DIR__ . '/stream-url-stat.dest.txt', '');
copy(__DIR__ . '/stream-url-stat.src.txt', __DIR__ . '/stream-url-stat.dest.txt');
printf("After copy, dest filesize is %d\n", filesize(__DIR__ . '/stream-url-stat.dest.txt'));

// Test stat() vs lstat().
symlink(__DIR__ . '/stream-url-stat.src.txt', __DIR__ . '/stream-url-stat.link.txt');
$keys = array(
        'dev' => 1,
        'ino' => 1,
        'mode' => 1,
        'size' => 1,
        'mtime' => 1,
        'ctime' => 1,
);
clearstatcache();
$stat = array_intersect_key(stat(__DIR__ . '/stream-url-stat.link.txt'), $keys);
$lstat = array_intersect_key(lstat(__DIR__ . '/stream-url-stat.link.txt'), $keys);

if ($stat != $lstat) {
        echo "\nOk, stat != lstat\n";
} else {
        echo "\nFail, stat == lstat\n";
}
foreach ($keys as $k => $v) {
    printf(" %5s: %-12s %-12s\n", $k, $stat[$k], $lstat[$k]);
}

?>
===DONE===

--CLEAN--
<?php
unlink(__DIR__ . '/stream-url-stat.src.txt');
unlink(__DIR__ . '/stream-url-stat.dest.txt');
unlink(__DIR__ . '/stream-url-stat.link.txt');

--EXPECTF--
After copy, dest filesize is 100

Ok, stat != lstat
   dev: %d%s %d%s
   ino: %d%s %d%s
  mode: %d%s %d%s
  size: %d%s %d%s
 mtime: %d%s %d%s
 ctime: %d%s %d%s
===DONE===
