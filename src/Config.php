<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Config;

function set(array $data)
{
    State::$table = $data + State::$table;
}

function get($key)
{
    if (!array_key_exists($key, State::$table)) {
        throw new \InvalidArgumentException("Configuration key '$key' is absent");
    }
    return State::$table[$key];
}

function read($file)
{
    $data = json_decode(file_get_contents($file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException("Malformed configuration file: " . json_last_error_msg());
    }
    set($data);
    State::$path = dirname($file);
}

function tryRead($file)
{
    if (!is_file($file)) {
        return;
    }
    read($file);
}

function getPath()
{
    return State::$path;
}

function resolvePath($path)
{
    return file_exists($path) ? realpath($path) : (getPath() . '/' . $path);
}

function getCacheLocation()
{
    if (get('cache') === null) {
        return null;
    }
    return resolvePath(get('cache'));
}

class State
{
    static $table = [
        'cache' => null,
        'blacklist' => [],
        'whitelist' => [],
        'redefinableInternals' => [],
    ];

    static $path;
}