<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Preprocessor;

require __DIR__ . "/Preprocessor/Source.php";
require __DIR__ . "/Preprocessor/Stream.php";
require __DIR__ . "/Preprocessor/Tokens.php";

use Patchwork\Exceptions;
use Patchwork\Utils;

const CALLBACKS = 'Patchwork\Preprocessor\CALLBACKS';
const BLACKLIST = 'Patchwork\Preprocessor\BLACKLIST';
const PREPROCESSED_FILES = 'Patchwork\Preprocessor\PREPROCESSED_FILES';

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function preprocess(Source $s)
{
    foreach ($GLOBALS[CALLBACKS] as $callback) {
        call_user_func($callback, $s);
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
    $GLOBALS[PREPROCESSED_FILES][$file] = true;
    return $resource;
}

function hasPreprocessed($file)
{
    return !empty($GLOBALS[PREPROCESSED_FILES][$file]);
}

function shouldPreprocess($file)
{
    foreach ($GLOBALS[BLACKLIST] as $pattern) {
        if (strpos(Utils\normalizePath($file), Utils\normalizePath($path)) === 0) {
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

$GLOBALS[CALLBACKS] = $GLOBALS[BLACKLIST] = $GLOBALS[PREPROCESSED_FILES] = array();
