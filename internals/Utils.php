<?php

namespace Patchwork\Utils;

function condense($string)
{
    return preg_replace("/\s*/", "", $string);
}

function upperBound(array $array, $value)
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

function rangeToReadableString($min, $max)
{
    if ($max == INF) {
        return "at least $min";
    } elseif ($min == $max) {
        return $min;
    }
    return "$min to $max";
}

function normalizePath($path)
{
    return strtr($path, "\\", "/");
}
