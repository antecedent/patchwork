<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Config;

use Patchwork\Exceptions;

const FILE_NAME = 'patchwork.json';

function locate($path)
{
    setRoot($path);
    while (file_exists($path) && is_readable($path)) {
        $file = rtrim($path, '/\\') . '/' . FILE_NAME;
        if (is_file($file)) {
            setRoot($path);
            read($file);
            break;
        }
        $path = dirname($path);
    }
}

function setRoot($root)
{
    State::$root = rtrim($root, '/\\');
}

function set(array $data)
{
    State::$table = $data + State::$table;
}

function get($key, $default = null)
{
    if (!array_key_exists($key, State::$table)) {
        if (func_num_args() == 2) {
            return $default;
        }
        throw new \InvalidArgumentException("Configuration key '$key' is absent");
    }
    return State::$table[$key];
}

function read($file)
{
    $file = resolvePath($file);
    $data = json_decode(file_get_contents($file), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException("Malformed configuration file $file (" . json_last_error_msg() . ")");
    }
    set((array) $data);
}

function resolvePath($path)
{
    if (file_exists($path) && realpath($path) == $path) {
        return $path;
    }
    if (State::$root === null) {
        return $path;
    }
    return State::$root . '/' . $path;
}

function getCachePath()
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

    static $root;
}
