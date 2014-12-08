<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2014 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Utils;

function traitsSupported()
{
    return version_compare(PHP_VERSION, "5.4", ">=");
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
    return preg_replace("/\s*/", "", $string);
}

function findFirstGreaterThan(array $array, $value)
{
    $low = 0;
    $high = count($array) - 1;
    if ($array[$high] <= $value) {
        return $high + 1;
    }
    while ($low < $high) {
        $mid = (int) (($low + $high) / 2);
        if ($array[$mid] <= $value) {
            $low = $mid + 1;
        } else {
            $high = $mid;
        }
    }
    return $low;
}

function interpretCallback($callback)
{
    if (is_object($callback)) {
        return interpretCallback(array($callback, "__invoke"));
    }
    if (is_array($callback)) {
        list($class, $method) = $callback;
        $instance = null;
        if (is_object($class)) {
            $instance = $class;
            $class = get_class($class);
        }
        $class = ltrim($class, "\\");
        return array($class, $method, $instance);
    }
    $callback = ltrim($callback, "\\");
    if (strpos($callback, "::")) {
        list($class, $method) = explode("::", $callback);
        return array($class, $method, null);
    }
    return array(null, $callback, null);
}

function callbackTargetDefined($callback, $shouldAutoload = false)
{
    list($class, $method, $instance) = interpretCallback($callback);
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
    if (traitsSupported()) {
        if (trait_exists($classOrTrait, $shouldAutoload)) {
            return true;
        }
    }
    return class_exists($classOrTrait, $shouldAutoload);
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
    list($class, $method) = interpretCallback($callback);
    if (isset($class)) {
        return new \ReflectionMethod($class, $method);
    }
    return new \ReflectionFunction($method);
}

function callbackToString($callback)
{
    list($class, $method) = interpretCallback($callback);
    if (isset($class)) {
        return $class . "::" . $method;
    }
    return $method;
}
