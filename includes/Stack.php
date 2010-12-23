<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Stack;

use Patchwork\Exceptions;

const OFFSETS = 'Patchwork\Stack\OFFSETS';

function push($offset)
{
    $GLOBALS[OFFSETS][] = $offset;
}

function pop()
{
    array_pop($GLOBALS[OFFSETS]);
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
    $frame = reset(all());
    if ($property) {
        return isset($frame[$property]) ? $frame[$property] : null;
    }
    return $frame;
}

function all()
{
    if (empty($GLOBALS[OFFSETS])) {
        throw new Exceptions\StackEmpty;
    }
    $backtrace = debug_backtrace();
    return array_slice($backtrace, count($backtrace) - end($GLOBALS[OFFSETS]));
}

$GLOBALS[OFFSETS] = array();
