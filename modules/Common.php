<?php

namespace Patchwork\Common;

function condense($string)
{
    return preg_replace('/\s*/', '', $string);
}

function get_upper_bound(array $array, $value)
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
    $namespace = ltrim($namespace, '\\');
    return function($class) use ($namespace, $dir) {
        $class = ltrim($class, '\\');
        if (strpos($class, $namespace) === 0) {
            $short_name = substr($class, $namespace ? (strlen($namespace) + 1) : 0);
            $file = $dir . '/' . strtr($short_name, '\\', '/') . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    };
}

function parse_callback($callback)
{
    if (is_object($callback)) {
        return parse_callable(array($callback, "__invoke"));
    }
    if (is_array($callback)) {
        list($class, $function) = $callback;
        if (is_object($class)) {
            $class = get_class($class);
        }
        return array($class, $function);
    } elseif (strpos($callback, '::')) {
        return explode('::', $callback, 2);
    }
    return array(null, $callback);
}

function append(&$array, $value)
{
    $array[] = $value;
    end($array);
    return key($array);
}

function callback_to_string($callback)
{
    list($class, $function) = parse_callback($callback);
    if ($class) {
        return "$class::$function";
    }
    return $function;
}

function range_to_string($min, $max)
{
    if ($max == INF) {
        return "at least $min";
    } elseif ($min == $max) {
        return $min;
    }
    return "$min to $max";
}

