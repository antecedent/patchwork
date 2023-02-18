<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2023 Ignas Rudaitis
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
require __DIR__ . '/CodeManipulation/Actions/RedefinitionOfLanguageConstructs.php';
require __DIR__ . '/CodeManipulation/Actions/ConflictPrevention.php';
require __DIR__ . '/CodeManipulation/Actions/RedefinitionOfNew.php';
require __DIR__ . '/CodeManipulation/Actions/Arguments.php';

use Patchwork\Exceptions;
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
    if (State::$cacheIndexFile === null) {
        $indexPath = Config\getCachePath() . '/index.csv';
        if (file_exists($indexPath)) {
            $table = array_map('str_getcsv', file($indexPath));
            foreach ($table as $row) {
                list($key, $value) = $row;
                State::$cacheIndex[$key] = $value;
            }
        }
        State::$cacheIndexFile = Stream::fopen($indexPath, 'a', false);
    }
    $hash = md5($file);
    $key = $hash;
    $suffix = 0;
    while (isset(State::$cacheIndex[$key]) && State::$cacheIndex[$key] !== $file) {
        $key = $hash . '_' . $suffix++;
    }
    if (!isset(State::$cacheIndex[$key])) {
        Stream::fwrite(State::$cacheIndexFile, sprintf("%s,\"%s\"\n", $key, $file));
        State::$cacheIndex[$key] = $file;
    }
    return Config\getCachePath() . '/' . $key . '.php';
}

function storeInCache(Source $source)
{
    $handle = Stream::fopen(getCachedPath($source->file), 'w', false);
    Stream::fwrite($handle, $source);
    Stream::fclose($handle);
}

function availableCached($file)
{
    if (!cacheEnabled()) {
        return false;
    }
    $cached = getCachedPath($file);
    return file_exists($cached) &&
           filemtime($file) <= filemtime($cached) &&
           Config\getTimestamp() <= filemtime($cached);
}

function internalToCache($file)
{
    if (!cacheEnabled()) {
        return false;
    }
    return strpos($file, Config\getCachePath() . '/') === 0
        || strpos($file, Config\getCachePath() . DIRECTORY_SEPARATOR) === 0;
}


function getContents($file)
{
    $handle = Stream::fopen($file, 'r', true);
    if ($handle === false) {
        return false;
    }
    $contents = '';
    while (!Stream::feof($handle)) {
        $contents .= Stream::fread($handle, 8192);
    }
    Stream::fclose($handle);
    return $contents;
}

function transformAndOpen($file)
{
    foreach (State::$importListeners as $listener) {
        $listener($file);
    }
    if (!internalToCache($file) && availableCached($file)) {
        return Stream::fopen(getCachedPath($file), 'r', false);
    }
    $code = getContents($file);
    if ($code === false) {
        return false;
    }
    $source = new Source($code);
    $source->file = $file;
    transform($source);
    if (!internalToCache($file) && cacheEnabled()) {
        storeInCache($source);
        return transformAndOpen($file);
    }
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    if ($resource) {
        fwrite($resource, $source);
        rewind($resource);
    }
    return $resource;
}

function prime($file)
{
    Stream::fclose(transformAndOpen($file));
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
    static $cacheIndex = [];
    static $cacheIndexFile;
}
