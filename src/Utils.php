<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Utils;

use Patchwork\Config;

const ALIASING_CODE = '
    namespace %s;
    function %s() {
        return call_user_func_array("%s", func_get_args());
    }
';

function clearOpcodeCaches()
{
    if (ini_get('wincache.ocenabled')) {
        wincache_refresh_if_changed();
    }
    if (ini_get('apc.enabled')) {
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

function findFirstGreaterThan(array $array, $value)
{
    $low = 0;
    $high = count($array) - 1;
    if ($array[$high] <= $value) {
        return $high + 1;
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

function getUserDefinedMethods()
{
    static $result = [];
    static $classCount = 0;
    static $traitCount = 0;
    $classes = getUserDefinedClasses();
    $traits = getUserDefinedTraits();
    $newClasses = array_slice($classes, $classCount);
    $newTraits = array_slice($traits, $traitCount);
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
    return stripos((string) $name, 'Patchwork\\') === 0;
}

function isForeignName($name)
{
    return !isOwnName($name);
}

function isMissedForeignName($name)
{
    return isForeignName($name) && Config\shouldWarnAbout($name);
}
