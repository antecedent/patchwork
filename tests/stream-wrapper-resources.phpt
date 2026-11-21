--TEST--
Bypassing the stream wrapper does not leave stream wrapper resources behind

--SKIPIF--
<?php
// PHP < 8.2 leaks a resource on every stream_wrapper_unregister() as well (php-src GH-8548)
class StreamWrapperResourcesProbe
{
    public $context;
}
stream_wrapper_unregister('file');
stream_wrapper_register('file', 'StreamWrapperResourcesProbe');
$before = count(get_resources('stream factory'));
stream_wrapper_unregister('file');
stream_wrapper_register('file', 'StreamWrapperResourcesProbe');
$leaks = count(get_resources('stream factory')) > $before;
stream_wrapper_unregister('file');
stream_wrapper_restore('file');
if ($leaks) {
    echo 'skip because stream_wrapper_unregister() leaks on this PHP version';
}

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";

$file = __DIR__ . '/stream-wrapper-resources.txt';
file_put_contents($file, 'contents');

$operations = function () use ($file) {
    clearstatcache();
    assert(file_exists($file));
    assert(file_get_contents($file) === 'contents');
    $handle = fopen($file, 'r');
    assert(file_exists(__DIR__ . '/includes/Functions.php'));
    assert(fread($handle, 100) === 'contents');
    fclose($handle);
};

$operations();
$before = count(get_resources('stream factory'));
for ($i = 0; $i < 100; $i++) {
    $operations();
}
$after = count(get_resources('stream factory'));

echo "Stream factory resources added: ", $after - $before, "\n";

$meta = stream_get_meta_data($handle = fopen($file, 'r'));
fclose($handle);
echo "file:// wrapper: ", $meta['wrapper_type'], "\n";

?>
===DONE===

--CLEAN--
<?php
unlink(__DIR__ . '/stream-wrapper-resources.txt');

--EXPECT--
Stream factory resources added: 0
file:// wrapper: user-space
===DONE===
