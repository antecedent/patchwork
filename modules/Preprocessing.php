<?php

namespace Patchwork\Preprocessing;

use Patchwork\Tokens;

const PREPROCESSORS = 'Patchwork\Preprocessing\PREPROCESSORS';
const PATHS = 'Patchwork\Preprocessing\PATHS';

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function preprocess(Source $s)
{
    foreach ($GLOBALS[PREPROCESSORS] as $preprocessor) {
        call_user_func($preprocessor, $s);
    }
}

function preprocess_string($code)
{
    $source = new Source(token_get_all($code));
    preprocess($source);
    return (string) $source;
}

function preprocess_and_eval($code)
{
    $prefix = "<?php ";
    return eval(substr(preprocess_string($prefix . $code), strlen($prefix)));
}

function preprocess_and_open($file)
{
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    preprocess($source);
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

function should_preprocess($file)
{
    foreach ($GLOBALS[PATHS] as $path) {
        if (preg_match($path, $file)) {
            return true;
        }
    }
    return false;
}

function prepend_code_to_functions($code)
{
    return function(Source $s) use ($code) {
        foreach ($s->find_all(T_FUNCTION) as $function) {
            $bracket   = $s->find_next(Tokens\LEFT_CURLY_BRACKET, $function);
            $semicolon = $s->find_next(Tokens\SEMICOLON, $function);
            if ($bracket < $semicolon) {
                $s->splice($code, $bracket + 1);
            }
        }
    };
}

function replace_tokens($search, $replacement)
{
    return function(Source $s) use ($search, $replacement) {
        foreach ($s->find_all($search) as $match) {
            $s->splice($replacement, $match, 1);
        }
    };
}

$GLOBALS[PREPROCESSORS] = $GLOBALS[PATHS] = array();
