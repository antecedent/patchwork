<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Stack;

use Patchwork\Exceptions;

function push($offset)
{
    State::$offsets[] = $offset;
}

function pop()
{
    array_pop(State::$offsets);
}

function pushFor($offset, $callback)
{
    push($offset);
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
    if (empty(State::$offsets)) {
        throw new Exceptions\StackEmpty;
    }
    return end(State::$offsets);
}

function all()
{
    $backtrace = debug_backtrace();
    return array_slice($backtrace, count($backtrace) - topOffset());
}

class State
{
    static $offsets = array();
}
