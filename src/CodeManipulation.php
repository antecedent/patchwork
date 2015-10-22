<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\CodeManipulation;

require __DIR__ . '/CodeManipulation/Source.php';
require __DIR__ . '/CodeManipulation/Stream.php';
require __DIR__ . '/CodeManipulation/Actions/Generic.php';
require __DIR__ . '/CodeManipulation/Actions/CallRerouting.php';
require __DIR__ . '/CodeManipulation/Actions/CodeManipulation.php';

use Patchwork\Exceptions;
use Patchwork\Utils;
use Patchwork\Config;

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
    $location = Config\getCacheLocation();
    if ($location === null) {
        return false;
    }
    if (!is_dir($location) || !is_writable($location)) {
        throw new Exceptions\CacheLocationUnavailable($location);
    }
    return true;
}

function getCachedPath($file)
{
    $file = realpath($file);
    return Config\getCacheLocation() . '/' . urlencode($file);
}

function availableCached($file)
{
    $file = realpath($file);
    return cacheEnabled() &&
    file_exists(getCachedPath($file)) &&
    filemtime($file) <= filemtime(getCachedPath($file));
}

function internalToCache($file)
{
    $file = realpath($file);
    if (!cacheEnabled()) {
        return false;
    }
    return strpos($file, Config\getCacheLocation() . '/') === 0
        || strpos($file, Config\getCacheLocation() . DIRECTORY_SEPARATOR) === 0;
}

function preprocessAndOpen($file)
{
    $file = realpath($file);
    foreach (State::$importListeners as $listener) {
        $listener($file);
    }
    if (!internalToCache($file) && availableCached($file)) {
        return fopen(getCachedPath($file), 'r');
    }
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    $source->file = $file;
    preprocess($source);
    if (!internalToCache($file) && cacheEnabled()) {
        file_put_contents(getCachedPath($file), $source);
        return preprocessAndOpen($file);
    }
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

function prime($file)
{
    $file = realpath($file);
    fclose(preprocessAndOpen($file));
}

function shouldPreprocess($file)
{
    $file = realpath($file);
    $blacklisted = false;
    foreach ((array) Config\get('blacklist') as $path) {
        if (strpos($file, Config\resolvePath($path)) === 0) {
            $blacklisted = true;
        }
    }
    if (!$blacklisted) {
        return true;
    }
    foreach ((array) Config\get('whitelist') as $path) {
        if (strpos($file, Config\resolvePath($path)) === 0) {
            return true;
        }
    }
    return false;
}

function attach($callbacks)
{
    State::$callbacks = array_merge(State::$callbacks, (array) $callbacks);
}

function onImport($listeners)
{
    State::$importListeners = array_merge(State::$importListeners, (array) $listeners);
}

class State
{
    static $callbacks = [];
    static $importListeners = [];
}
