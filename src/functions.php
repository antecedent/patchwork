<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork;

function redefine($subject, callable $content)
{
    $handle = null;
    foreach (array_slice(func_get_args(), 1) as $content) {
        $handle = CallRerouting\connect($subject, $content, $handle);
    }
    $handle->silence();
    return $handle;
}

function relay(array $args = null)
{
    return CallRerouting\relay($args);
}

function fallBack()
{
    throw new Exceptions\NoResult;
}

function restore(CallRerouting\Handle $handle)
{
    $handle->expire();
}

function restoreAll()
{
    CallRerouting\disconnectAll();
}

function silence(CallRerouting\Handle $handle)
{
    $handle->silence();
}

function assertEventuallyDefined(CallRerouting\Handle $handle)
{
    $handle->unsilence();
}

function getClass()
{
    return Stack\top('class');
}

function getCalledClass()
{
    return Stack\topCalledClass();
}

function getFunction()
{
    return Stack\top('function');
}

function getMethod()
{
    return getClass() . '::' . getFunction();
}

function configure()
{
    Config\locate();
}

function hasMissed($callable)
{
    return Utils\callableWasMissed($callable);
}

function always($value)
{
    return function() use ($value) {
        return $value;
    };
}
