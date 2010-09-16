<?php

namespace Patchwork;

const FILES_TO_PATCH = 'Patchwork\FILES_TO_PATCH';
const FILES_NOT_TO_PATCH = 'Patchwork\FILES_NOT_TO_PATCH';

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function will_patch($path, array $subpatterns = array())
{
    $GLOBALS[FILES_TO_PATCH][] = pattern_to_regex($path, $subpatterns);
}

function will_not_patch($path, array $subpatterns = array())
{
    $GLOBALS[FILES_NOT_TO_PATCH][] = pattern_to_regex($path, $subpatterns);
}

function pattern_to_regex($pattern, array $subpatterns)
{
    $pattern = '<' . preg_quote(normalize_path($pattern)) . '>';
    return strtr($pattern, quote_subpattern_keys($subpatterns + get_default_subpatterns()));
}

function quote_subpattern_keys(array $subpatterns)
{
    $result = array();
    foreach ($subpatterns as $key => $subpattern) {
        $result[preg_quote("<$key>")] = $subpattern;
    }
    return $result;
}

function get_default_subpatterns()
{
    return array(
        '*' => '.*',        
        '?' => '[^\\/]*',
    );
}

function normalize_path($path)
{
    return strtr($path, DIRECTORY_SEPARATOR, '/');
}

function should_patch($file)
{
    return !match_path($file, $GLOBALS[FILES_NOT_TO_PATCH]) &&
           match_path($file, $GLOBALS[FILES_TO_PATCH]);;
}

function match_path($path, array $patterns)
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, normalize_path($path))) {
            return true;            
        }
    }
    return false;
}

function patch_file($file)
{
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    patch($source);
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

$GLOBALS[FILES_TO_PATCH] = $GLOBALS[FILES_NOT_TO_PATCH] = array();
