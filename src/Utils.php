<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Utils;

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

function reflectCallback($callback)
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
    # TODO optimize
    return array_merge(get_defined_functions()['user'], getUserDefinedMethods());
}

function getUserDefinedMethods()
{
    # TODO optimize
    $classes = array_merge(getUserDefinedClasses(), get_declared_traits());
    $mapping = function($class) {
        $methods = get_class_methods($class);
        return array_map(function ($method) use ($class) {
            return "$class::$method";
        }, $methods);
    };
    return call_user_func_array('array_merge', array_map($mapping, $classes));
}

function getUserDefinedClasses()
{
    # TODO optimize
    $all = get_declared_classes();
    return array_filter($all, function($class) {
        return (new \ReflectionClass($class))->isUserDefined();
    });
}

function matchWildcard($wildcard, $subject)
{
    # TODO optimize
    $table = ['*' => '.*', '{' => '(', '}' => ')', ' ' => ''];
    return preg_match('/' . strtr($wildcard, $table) . '/', $subject);
}

function isOwnName($name)
{
    return stripos((string) $name, 'Patchwork\\') === 0;
}

function isForeignName($name)
{
    return !isOwnName($name);
}
