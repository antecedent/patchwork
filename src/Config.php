<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Config;

use Patchwork\Utils;
use Patchwork\Exceptions;

const FILE_NAME = 'patchwork.json';

function locate()
{
    $alreadyRead = [];
    $paths = array_map('dirname', get_included_files());
    $paths[] = dirname($_SERVER['PHP_SELF']);
    foreach ($paths as $path) {
        while (dirname($path) !== $path) {
            $file = $path . '/' . FILE_NAME;
            if (is_file($file) && !isset($alreadyRead[$file])) {
                read($file);
                $alreadyRead[$file] = true;
            }
            $path = dirname($path);
        }
    }
}

function read($file)
{
    $data = json_decode(file_get_contents($file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $message = json_last_error_msg();
        throw new Exceptions\ConfigMalformed($file, $message);
    }
    set($data, $file);
}

function set(array $data, $file)
{
    $keys = array_keys($data);
    $list = ['blacklist', 'whitelist', 'suppress-warnings', 'cache-path'];
    $unknown = array_diff($keys, $list);
    if ($unknown != []) {
        throw new Exceptions\ConfigKeyNotRecognized(reset($unknown), $list, $file);
    }
    $root = dirname($file);
    setBlacklist(get($data, 'blacklist'), $root);
    setWhitelist(get($data, 'whitelist'), $root);
    setSuppressedWarnings(get($data, 'suppress-warnings'));
    setCachePath(get($data, 'cache-path'), $root);
}

function get(array $data, $key)
{
    return isset($data[$key]) ? $data[$key] : null;
}

function setBlacklist($data, $root)
{
    merge(State::$blacklist, resolvePaths($data, $root));
}

function isListed($path, array $list)
{
    $path = rtrim($path, '\\/');
    foreach ($list as $item) {
        if (strpos($path, $item) === 0) {
            return true;
        }
    }
    return false;
}

function isBlacklisted($path)
{
    return isListed($path, State::$blacklist);
}

function setWhitelist($data, $root)
{
    merge(State::$whitelist, resolvePaths($data, $root));
}

function isWhitelisted($path)
{
    return isListed($path, State::$whitelist);
}

function setSuppressedWarnings($data)
{
    merge(State::$suppressedWarnings, $data);
}

function shouldWarnAbout($callable)
{
    $callable = Utils\callableToString($callable);
    foreach (State::$suppressedWarnings as $wildcard) {
        if (Utils\wildcardMatches($wildcard, $callable)) {
            return false;
        }
    }
    return true;
}

function setCachePath($data, $root)
{
    if ($data === null) {
        return;
    }
    $path = resolvePath($data, $root);
    if (State::$cachePath !== null && State::$cachePath !== $path) {
        throw new Exceptions\CachePathConflict(State::$cachePath, $path);
    }
    State::$cachePath = $path;
}

function getCachePath()
{
    return State::$cachePath;
}

function resolvePath($path, $root)
{
    if ($path === null) {
        return null;
    }
    if (file_exists($path) && realpath($path) === $path) {
        return $path;
    }
    return realpath($root . '/' . $path);
}

function resolvePaths($paths, $root)
{
    if ($paths === null) {
        return [];
    }
    $result = [];
    foreach ((array) $paths as $path) {
        $result[] = resolvePath($path, $root);
    }
    return $result;
}

function merge(array &$target, $source)
{
    $target = array_merge($target, (array) $source);
}

class State
{
    static $blacklist = [];
    static $whitelist = [];
    static $suppressedWarnings = [];
    static $cachePath;
}
