<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Config;

use Patchwork\Utils;
use Patchwork\Exceptions;
use Patchwork\CodeManipulation\Actions\RedefinitionOfLanguageConstructs;

const FILE_NAME = 'patchwork.json';

function locate()
{
    $alreadyRead = [];
    $paths = array_map('dirname', get_included_files());
    $paths[] = dirname($_SERVER['PHP_SELF']);
    $paths[] = getcwd();
    foreach ($paths as $path) {
        while (dirname($path) !== $path) {
            $file = $path . DIRECTORY_SEPARATOR . FILE_NAME;
            if (is_file($file) && !isset($alreadyRead[$file])) {
                read($file);
                State::$timestamp = max(filemtime($file), State::$timestamp);
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
    $list = ['blacklist', 'whitelist', 'cache-path', 'redefinable-internals', 'new-keyword-redefinable'];
    $unknown = array_diff($keys, $list);
    if ($unknown != []) {
        throw new Exceptions\ConfigKeyNotRecognized(reset($unknown), $list, $file);
    }
    $root = dirname($file);
    setBlacklist(get($data, 'blacklist'), $root);
    setWhitelist(get($data, 'whitelist'), $root);
    setCachePath(get($data, 'cache-path'), $root);
    setRedefinableInternals(get($data, 'redefinable-internals'), $root);
    setNewKeywordRedefinability(get($data, 'new-keyword-redefinable'), $root);
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
		if (!is_string($item)) {
			$item = chr($item);
		}
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

function getDefaultRedefinableInternals()
{
    return [
        'preg_replace_callback',
        'spl_autoload_register',
        'iterator_apply',
        'header_register_callback',
        'call_user_func',
        'call_user_func_array',
        'forward_static_call',
        'forward_static_call_array',
        'register_shutdown_function',
        'register_tick_function',
        'unregister_tick_function',
        'ob_start',
        'usort',
        'uasort',
        'uksort',
        'array_reduce',
        'array_intersect_ukey',
        'array_uintersect',
        'array_uintersect_assoc',
        'array_intersect_uassoc',
        'array_uintersect_uassoc',
        'array_uintersect_uassoc',
        'array_diff_ukey',
        'array_udiff',
        'array_udiff_assoc',
        'array_diff_uassoc',
        'array_udiff_uassoc',
        'array_udiff_uassoc',
        'array_filter',
        'array_map',
        'libxml_set_external_entity_loader',
    ];
}

function getRedefinableInternals()
{
    if (!empty(State::$redefinableInternals)) {
        return array_merge(State::$redefinableInternals, getDefaultRedefinableInternals());
    }
    return [];
}

function setRedefinableInternals($names)
{
    merge(State::$redefinableInternals, $names);
    $constructs = array_intersect(State::$redefinableInternals, getSupportedLanguageConstructs());
    State::$redefinableLanguageConstructs = array_merge(State::$redefinableLanguageConstructs, $constructs);
    State::$redefinableInternals = array_diff(State::$redefinableInternals, $constructs);
}

function setNewKeywordRedefinability($value)
{
    State::$newKeywordRedefinable = State::$newKeywordRedefinable || $value;
}

function getRedefinableLanguageConstructs()
{
    return State::$redefinableLanguageConstructs;
}

function getSupportedLanguageConstructs()
{
    return array_keys(RedefinitionOfLanguageConstructs\getMappingOfConstructs());
}

function isNewKeywordRedefinable()
{
    return State::$newKeywordRedefinable;
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

function getTimestamp()
{
    return State::$timestamp;
}

class State
{
    static $blacklist = [];
    static $whitelist = [];
    static $cachePath;
    static $redefinableInternals = [];
    static $redefinableLanguageConstructs = [];
    static $newKeywordRedefinable = false;
    static $timestamp = 0;
}
