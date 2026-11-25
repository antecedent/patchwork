--TEST--
A directory can be read, rewound and closed while Patchwork is the only stream wrapper

--FILE--
<?php

ini_set('zend.assertions', 1);
error_reporting(E_ALL);

require __DIR__ . "/../Patchwork.php";

$handle = opendir(__DIR__ . "/includes/DirectoryListing");

// stop here, rather than read a broken handle, when the handle Patchwork holds is not the one it should
$held = stream_get_meta_data($handle)["wrapper_data"]->resource;

if (!is_resource($held)) {
    echo "the stream wrapper holds ", gettype($held), " instead of a directory handle\n";
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

echo is_resource($held) ? "open" : "closed", "\n";

?>
===DONE===

--EXPECT--
[".","..","alpha.txt"]
[".","..","alpha.txt"]
closed
===DONE===
