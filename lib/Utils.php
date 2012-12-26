<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Utils;

function condense($string)
{
    return preg_replace("/\s*/", "", $string);
}

function getUpperBound(array $array, $value)
{
    $count = count($array);
    $first = 0;
    while ($count > 0) {
        $i = $first;
        $step = $count >> 1;
        $i += $step;
        if ($value >= $array[$i]) {
               $first = ++$i;
               $count -= $step + 1;
          } else {
              $count = $step;
          }
    }
    return $first; 
}

function parseCallback($callback)
{
    if (is_object($callback)) {
        return parseCallback(array($callback, "__invoke"));
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
    list($class, $method, $instance) = parseCallback($callback);
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
    if (version_compare(PHP_VERSION, "5.4", ">=")) {
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
    return strtr($path, "\\", "/");
}

function reflectCallback($callback)
{
    if ($callback instanceof \Closure) {
        return new \ReflectionFunction($callback);
    }
    list($class, $method) = parseCallback($callback);
    if (isset($class)) {
        return new \ReflectionMethod($class, $method);
    }
    return new \ReflectionFunction($method);
}

function callbackToString($callback)
{
    list($class, $method) = parseCallback($callback);
    if (isset($class)) {
        return $class . "::" . $method;
    }
    return $method;
}
