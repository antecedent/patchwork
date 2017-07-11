<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2017 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Utils;

use Patchwork\Config;
use Patchwork\CallRerouting;
use Patchwork\CodeManipulation;

const ALIASING_CODE = '
    namespace %s;
    function %s() {
        return call_user_func_array("%s", func_get_args());
    }
';

function clearOpcodeCaches()
{
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    if (ini_get('wincache.ocenabled')) {
        wincache_refresh_if_changed();
    }
    if (ini_get('apc.enabled') && function_exists('apc_clear_cache')) {
        apc_clear_cache();
    }
}

function generatorsSupported()
{
    return version_compare(PHP_VERSION, "5.5", ">=");
}

function runningOnHHVM()
{
    return defined("HHVM_VERSION");
}

function condense($string)
{
    return preg_replace('/\s*/', '', $string);
}

function indexOfFirstGreaterThan(array $array, $value)
{
    $low = 0;
    $high = count($array) - 1;
    if (empty($array) || $array[$high] <= $value) {
        return -1;
    }
    while ($low < $high) {
        $mid = (int)(($low + $high) / 2);
        if ($array[$mid] <= $value) {
            $low = $mid + 1;
        } else {
            $high = $mid;
        }
    }
    return $low;
}

function indexOfLastNotGreaterThan(array $array, $value)
{
    if (empty($array)) {
        return -1;
    }
    $result = indexOfFirstGreaterThan($array, $value);
    if ($result === -1) {
        $result = count($array) - 1;
    }
    while ($array[$result] > $value) {
        $result--;
    }
    return $result;
}

function firstGreaterThan(array $array, $value, $default = INF)
{
    $index = indexOfFirstGreaterThan($array, $value);
    return ($index !== -1) ? $array[$index] : $default;
}

function lastNotGreaterThan(array $array, $value, $default = INF)
{
    $index = indexOfLastNotGreaterThan($array, $value);
    return ($index !== -1) ? $array[$index] : $default;
}

function allWithinRange(array $array, $low, $high)
{
    $low--;
    $high++;
    $index = indexOfFirstGreaterThan($array, $low);
    if ($index === -1) {
        return [];
    }
    $result = [];
    while ($index < count($array) && $array[$index] < $high) {
        $result[] = $array[$index];
        $index++;
    }
    return $result;
}

function interpretCallable($callback)
{
    if (is_object($callback)) {
        return interpretCallable([$callback, "__invoke"]);
    }
    if (is_array($callback)) {
        list($class, $method) = $callback;
        $instance = null;
        if (is_object($class)) {
            $instance = $class;
            $class = get_class($class);
        }
        $class = ltrim($class, "\\");
        return [$class, $method, $instance];
    }
    $callback = ltrim($callback, "\\");
    if (strpos($callback, "::")) {
        list($class, $method) = explode("::", $callback);
        return [$class, $method, null];
    }
    return [null, $callback, null];
}

function callableDefined($callable, $shouldAutoload = false)
{
    list($class, $method, $instance) = interpretCallable($callable);
    if ($instance !== null) {
        return true;
    }
    if (isset($class)) {
        return classOrTraitExists($class, $shouldAutoload) &&
               method_exists($class, $method);
    }
    return function_exists($method);
}

function classOrTraitExists($classOrTrait, $shouldAutoload = true)
{
    return class_exists($classOrTrait, $shouldAutoload)
        || trait_exists($classOrTrait, $shouldAutoload);
}

function append(&$array, $value)
{
    $array[] = $value;
    end($array);
    return key($array);
}

function appendUnder(&$array, $path, $value)
{
    foreach ((array) $path as $key) {
        if (!isset($array[$key])) {
            $array[$key] = [];
        }
        $array = &$array[$key];
    }
    return append($array, $value);
}

function access($array, $path, $default = null)
{
    foreach ((array) $path as $key) {
        if (!isset($array[$key])) {
            return $default;
        }
        $array = $array[$key];
    }
    return $array;
}

function normalizePath($path)
{
    return rtrim(strtr($path, "\\", "/"), "/");
}

function reflectCallable($callback)
{
    if ($callback instanceof \Closure) {
        return new \ReflectionFunction($callback);
    }
    list($class, $method) = interpretCallable($callback);
    if (isset($class)) {
        return new \ReflectionMethod($class, $method);
    }
    return new \ReflectionFunction($method);
}

function callableToString($callback)
{
    list($class, $method) = interpretCallable($callback);
    if (isset($class)) {
        return $class . "::" . $method;
    }
    return $method;
}

function alias($namespace, array $mapping)
{
    foreach ($mapping as $original => $aliases) {
        $original = ltrim(str_replace('\\', '\\\\', $namespace) . '\\\\' . $original, '\\');
        foreach ((array) $aliases as $alias) {
            eval(sprintf(ALIASING_CODE, $namespace, $alias, $original));
        }
    }
}

function getUserDefinedCallables()
{
    return array_merge(get_defined_functions()['user'], getUserDefinedMethods());
}

function getRedefinableCallables()
{
    return array_merge(getUserDefinedCallables(), Config\getRedefinableInternals());
}

function getUserDefinedMethods()
{
    static $result = [];
    static $classCount = 0;
    static $traitCount = 0;
    $classes = getUserDefinedClasses();
    $traits = getUserDefinedTraits();
    if (runningOnHHVM()) {
        # cannot rely on the order of get_declared_classes()
        static $previousClasses = [];
        static $previousTraits = [];
        $newClasses = array_diff($classes, $previousClasses);
        $newTraits = array_diff($traits, $previousTraits);
        $previousClasses = $classes;
        $previousTraits = $traits;
    } else {
        $newClasses = array_slice($classes, $classCount);
        $newTraits = array_slice($traits, $traitCount);
    }
    foreach (array_merge($newClasses, $newTraits) as $newClass) {
        foreach (get_class_methods($newClass) as $method) {
            $result[] = $newClass . '::' . $method;
        }
    }
    $classCount = count($classes);
    $traitCount = count($traits);
    return $result;
}

function getUserDefinedClasses()
{
    static $classCutoff;
    $classes = get_declared_classes();
    if (!isset($classCutoff)) {
        $classCutoff = count($classes);
        for ($i = 0; $i < count($classes); $i++) {
            if ((new \ReflectionClass($classes[$i]))->isUserDefined()) {
                $classCutoff = $i;
                break;
            }
        }
    }
    return array_slice($classes, $classCutoff);
}

function getUserDefinedTraits()
{
    static $traitCutoff;
    $traits = get_declared_traits();
    if (!isset($traitCutoff)) {
        $traitCutoff = count($traits);
        for ($i = 0; $i < count($traits); $i++) {
            $methods = get_class_methods($traits[$i]);
            if (empty($methods)) {
                continue;
            }
            list($first) = $methods;
            if ((new \ReflectionMethod($traits[$i], $first))->isUserDefined()) {
                $traitCutoff = $i;
                break;
            }
        }
    }
    return array_slice($traits, $traitCutoff);
}

function matchWildcard($wildcard, array $subjects)
{
    $table = ['*' => '.*', '{' => '(', '}' => ')', ' ' => '', '\\' => '\\\\'];
    $pattern = '/' . strtr($wildcard, $table) . '/i';
    return preg_grep($pattern, $subjects);
}

function wildcardMatches($wildcard, $subject)
{
    return matchWildcard($wildcard, [$subject]) == [$subject];
}

function isOwnName($name)
{
    return stripos((string) $name, 'Patchwork\\') === 0
        && stripos((string) $name, CallRerouting\INTERNAL_REDEFINITION_NAMESPACE . '\\') !== 0;
}

function isForeignName($name)
{
    return !isOwnName($name);
}

function markMissedCallables()
{
    State::$missedCallables = array_map('strtolower', getUserDefinedCallables());
}

function getMissedCallables()
{
    return State::$missedCallables;
}

function callableWasMissed($name)
{
    return in_array(strtolower($name), getMissedCallables());
}

function endsWith($haystack, $needle)
{
    if (strlen($haystack) === strlen($needle)) {
        return $haystack === $needle;
    }
    if (strlen($haystack) < strlen($needle)) {
        return false;
    }
    return substr($haystack, -strlen($needle)) === $needle;
}

function wasRunAsConsoleApp()
{
    global $argv;
    return isset($argv) && (
        endsWith($argv[0], 'patchwork.phar') || endsWith($argv[0], 'Patchwork.php')
    );
}

function args()
{
    return func_get_args();
}

class State
{
    static $missedCallables = [];
}
