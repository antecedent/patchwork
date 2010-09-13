<?php

namespace Patchwork;

const PATCHED_SOURCE_DIR = 'Patchwork\PATCHED_SOURCE_DIR';
const FILES_TO_PATCH = 'Patchwork\FILE_PATTERNS';

function patch_file($file)
{
    $resource = fopen('php://memory', 'rb+');
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    patch($source);
    fwrite($resource, source_to_string($source));
    rewind($resource);
    return $resource;
}

function can_patch($file)
{
    return true;
}