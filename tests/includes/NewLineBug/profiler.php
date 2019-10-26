<?php
include_once(__DIR__ . "/includeme.php");

Patchwork\redefine("time", function () {
    return strtotime("Dec 31, 1999");
});

echo date("Y-m-d", time());
