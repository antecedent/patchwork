<?php

namespace Patchwork\Preprocessing;

use Patchwork\Tokens;
use Patchwork\Exceptions;
use Patchwork\Utils;

const PREPROCESSORS  = 'Patchwork\Preprocessing\PREPROCESSORS';
const EXCLUDED_PATHS = 'Patchwork\Preprocessing\EXCLUDED_PATHS';

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function preprocess(Source $s)
{
    foreach ($GLOBALS[PREPROCESSORS] as $preprocessor) {
        call_user_func($preprocessor, $s);
    }
}

function preprocessString($code)
{
    $source = new Source(token_get_all($code));
    preprocess($source);
    return (string) $source;
}

function preprocessAndEval($code)
{
    $prefix = "<?php ";
    return eval(substr(preprocessString($prefix . $code), strlen($prefix)));
}

function preprocessAndOpen($file)
{
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    preprocess($source);
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

function shouldPreprocess($file)
{
    foreach ($GLOBALS[EXCLUDED_PATHS] as $pattern) {
        if (strpos(Utils\normalizePath($file), Utils\normalizePath($pattern)) !== false) {
            return false;
        }
    }
    return true;
}

function prependCodeToFunctions($code)
{
    return function(Source $s) use ($code) {
        foreach ($s->findAll(T_FUNCTION) as $function) {
            $bracket   = $s->findNext(Tokens\LEFT_CURLY_BRACKET, $function);
            $semicolon = $s->findNext(Tokens\SEMICOLON, $function);
            if ($bracket < $semicolon) {
                $s->splice($code, $bracket + 1);
            }
        }
    };
}

function replaceTokens($search, $replacement)
{
    return function(Source $s) use ($search, $replacement) {
        foreach ($s->findAll($search) as $match) {
            $s->splice($replacement, $match, 1);
        }
    };
}

$GLOBALS[PREPROCESSORS] = $GLOBALS[EXCLUDED_PATHS] = array();
