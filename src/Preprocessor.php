<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor;

require __DIR__ . "/Preprocessor/Source.php";
require __DIR__ . "/Preprocessor/Stream.php";
require __DIR__ . "/Preprocessor/Callbacks/Generic.php";
require __DIR__ . "/Preprocessor/Callbacks/Interceptor.php";
require __DIR__ . "/Preprocessor/Callbacks/Preprocessor.php";

use Patchwork\Exceptions;
use Patchwork\Utils;

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function preprocess(Source $s)
{
    foreach (State::$callbacks as $callback) {
        $callback($s);
    }
}

function preprocessString($code)
{
    $source = new Source(token_get_all($code));
    preprocess($source);
    return (string) $source;
}

function preprocessForEval($code)
{
    $prefix = "<?php ";
    return substr(preprocessString($prefix . $code), strlen($prefix));
}

function cacheEnabled()
{
    return State::$cacheLocation !== null;
}

function getCachedPath($file)
{
    return State::$cacheLocation . '/' . urlencode($file);
}

function availableCached($file)
{
    return cacheEnabled() &&
           file_exists(getCachedPath($file)) &&
           filemtime($file) <= filemtime(getCachedPath($file));
}

function preprocessAndOpen($file)
{
    foreach (State::$importListeners as $listener) {
        $listener($file);
    }
    if (availableCached($file)) {
        return fopen(getCachedPath($file), 'r');
    }
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    $source->file = $file;
    preprocess($source);
    if (cacheEnabled()) {
        file_put_contents(getCachedPath($file), $source);
        return preprocessAndOpen($file);
    }
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

function shouldPreprocess($file)
{
    foreach (State::$blacklist as $path) {
        if (strpos(Utils\normalizePath($file), $path) === 0) {
            return false;
        }
    }
    return true;
}

function attach($callbacks)
{
    State::$callbacks = array_merge(State::$callbacks, (array) $callbacks);
}

function onImport($listeners)
{
    State::$importListeners = array_merge(State::$importListeners, (array) $listeners);
}

function exclude($path)
{
    State::$blacklist[] = Utils\normalizePath($path);
}

function setCacheLocation($location, $assertWritable = true)
{
    $location = Utils\normalizePath($location);
    if (!is_writable($location)) {
        if ($assertWritable) {
            throw new Exceptions\CacheLocationUnavailable($location);
        }
        return false;
    }
    State::$cacheLocation = $location;
    return true;
}

class State
{
    static $callbacks = array();
    static $blacklist = array();
    static $importListeners = array();
    static $cacheLocation;
}
