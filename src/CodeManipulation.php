<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation;

require __DIR__ . '/CodeManipulation/Source.php';
require __DIR__ . '/CodeManipulation/Stream.php';
require __DIR__ . '/CodeManipulation/Actions/Generic.php';
require __DIR__ . '/CodeManipulation/Actions/CallRerouting.php';
require __DIR__ . '/CodeManipulation/Actions/CodeManipulation.php';
require __DIR__ . '/CodeManipulation/Actions/Namespaces.php';
require __DIR__ . '/CodeManipulation/Actions/RedefinitionOfInternals.php';

use Patchwork\Exceptions;
use Patchwork\Utils;
use Patchwork\Config;

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function transform(Source $s)
{
    foreach (State::$actions as $action) {
        $action($s);
    }
}

function transformString($code)
{
    $source = new Source($code);
    transform($source);
    return (string) $source;
}

function transformForEval($code)
{
    $prefix = "<?php ";
    return substr(transformString($prefix . $code), strlen($prefix));
}

function cacheEnabled()
{
    $location = Config\getCachePath();
    if ($location === null) {
        return false;
    }
    if (!is_dir($location) || !is_writable($location)) {
        throw new Exceptions\CachePathUnavailable($location);
    }
    return true;
}

function getCachedPath($file)
{
    $file = str_replace(DIRECTORY_SEPARATOR, '/', $file);
    $segments = explode('/', $file);
    return Config\getCachePath() . '/' . join('/', array_map('urlencode', $segments));
}

function storeInCache(Source $source)
{
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $source->file);
    $dirs = explode('/', $path);
    $file = array_pop($dirs);
    $cachePath = Config\getCachePath();
    foreach ($dirs as $dir) {
        $cachePath .= '/' . urlencode($dir);
        if (!is_dir($cachePath)) {
            mkdir($cachePath);
        }
    }
    $cachePath .= '/' . urlencode($file);
    file_put_contents($cachePath, $source);
}

function availableCached($file)
{
    return cacheEnabled() &&
           file_exists(getCachedPath($file)) &&
           filemtime($file) <= filemtime(getCachedPath($file));
}

function internalToCache($file)
{
    if (!cacheEnabled()) {
        return false;
    }
    return strpos($file, Config\getCachePath() . '/') === 0
        || strpos($file, Config\getCachePath() . DIRECTORY_SEPARATOR) === 0;
}

function transformAndOpen($file)
{
    foreach (State::$importListeners as $listener) {
        $listener($file);
    }
    if (!internalToCache($file) && availableCached($file)) {
        return fopen(getCachedPath($file), 'r');
    }
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source($code);
    $source->file = $file;
    transform($source);
    if (!internalToCache($file) && cacheEnabled()) {
        storeInCache($source);
        return transformAndOpen($file);
    }
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

function prime($file)
{
    fclose(transformAndOpen($file));
}

function shouldTransform($file)
{
    return !Config\isBlacklisted($file) || Config\isWhitelisted($file);
}

function register($actions)
{
    State::$actions = array_merge(State::$actions, (array) $actions);
}

function onImport($listeners)
{
    State::$importListeners = array_merge(State::$importListeners, (array) $listeners);
}

class State
{
    static $actions = [];
    static $importListeners = [];
}
