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

function autoload($namespace, $dir)
{
    $namespace = ltrim($namespace, "\\");
    return function($class) use ($namespace, $dir) {
        $class = ltrim($class, "\\");
        if (strpos($class, $namespace) === 0) {
            $shortName = substr($class, $namespace ? (strlen($namespace) + 1) : 0);
            $file = $dir . "/" . strtr($shortName, "\\", "/") . ".php";
            if (is_file($file)) {
                require $file;
            }
        }
    };
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
        return array($class, $function);
    } elseif (strpos($callback, "::")) {
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

function callBySignature($callback, array $arguments)
{
    $parameters = reflectCallback($callback)->getParameters();
    foreach ($arguments as $offset => $argument) {
        if (!isset($parameters[$offset]) || !$parameters[$offset]->isPassedByReference()) {
            unset($arguments[$offset]);
            $arguments[$offset] = $argument;
        }
    }
    return call_user_func_array($callback, $arguments);
}
