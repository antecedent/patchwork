--TEST--
A directory opened through another stream wrapper registered BEFORE importing Patchwork can be read, rewound and closed

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/includes/StreamWrapperForTesting.php";
require __DIR__ . "/../Patchwork.php";

$handle = opendir(__DIR__ . "/includes/DirectoryListing");

// stop here, rather than read a broken handle, when the handle Patchwork holds is not the one it should
$held = stream_get_meta_data($handle)["wrapper_data"]->resource;

if (!is_object($held)) {
    echo "the stream wrapper holds ", gettype($held), " instead of the other wrapper's object\n";
    exit;
}

$list = function () use ($handle) {
    $entries = [];
    while (($entry = readdir($handle)) !== false) {
        $entries[] = $entry;
    }
    sort($entries);
    return json_encode($entries);
};

echo $list(), "\n";
rewinddir($handle);
echo $list(), "\n";
closedir($handle);

echo StreamWrapperForTesting::$directoriesClosed, "\n";

?>
===DONE===

--EXPECT--
[".","..","alpha.txt"]
[".","..","alpha.txt"]
1
===DONE===
