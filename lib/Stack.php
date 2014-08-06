<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2014 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Stack;

use Patchwork\Exceptions;

function push($offset, $calledClass)
{
    State::$items[] = array($offset, $calledClass);
}

function pop()
{
    array_pop(State::$items);
}

function pushFor($offset, $calledClass, $callback)
{
    push($offset, $calledClass);
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
    static $items = array();
}
