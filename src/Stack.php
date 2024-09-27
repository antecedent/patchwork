<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Stack;

use Patchwork\Exceptions;

function push($offset, $calledClass, ?array $argsOverride = null)
{
    State::$items[] = [$offset, $calledClass, $argsOverride];
}

function pop()
{
    array_pop(State::$items);
}

function pushFor($offset, $calledClass, $callback, ?array $argsOverride = null)
{
    push($offset, $calledClass, $argsOverride);
    try {
        $callback();
    } catch (\Exception $e) {
        $exception = $e;
    }
    pop();
    if (isset($exception)) {
        throw $exception;
    }
}

function top($property = null)
{
    $all = all();
    $frame = reset($all);
    $argsOverride = topArgsOverride();
    if ($argsOverride !== null) {
        $frame["args"] = $argsOverride;
    }
    if ($property) {
        return isset($frame[$property]) ? $frame[$property] : null;
    }
    return $frame;
}

function topOffset()
{
    if (empty(State::$items)) {
        throw new Exceptions\StackEmpty;
    }
    list($offset, $calledClass) = end(State::$items);
    return $offset;
}

function topCalledClass()
{
    if (empty(State::$items)) {
        throw new Exceptions\StackEmpty;
    }
    list($offset, $calledClass) = end(State::$items);
    return $calledClass;
}

function topArgsOverride()
{
    if (empty(State::$items)) {
        throw new Exceptions\StackEmpty;
    }
    list($offset, $calledClass, $argsOverride) = end(State::$items);
    return $argsOverride;
}

function all()
{
    $backtrace = debug_backtrace();
    return array_slice($backtrace, count($backtrace) - topOffset());
}

function allCalledClasses()
{
    return array_map(function($item) {
        list($offset, $calledClass) = $item;
        return $calledClass;
    }, State::$items);
}

class State
{
    static $items = [];
}
