<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
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
        list($class, $function) = $callback;
        if (is_object($class)) {
            $class = get_class($class);
        }
        $class = ltrim($class, "\\");
        return array($class, $function);
    }
    $callback = ltrim($callback, "\\");
    if (strpos($callback, "::")) {
        return explode("::", $callback, 2);
    }
    return array(null, $callback);
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
