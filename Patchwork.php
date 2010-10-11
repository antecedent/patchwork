<?php

namespace Patchwork;

require_once __DIR__ . "/internals/Filtering.php";
require_once __DIR__ . "/internals/Preprocessing.php";
require_once __DIR__ . "/internals/Splices.php";
require_once __DIR__ . "/internals/Tokens.php";
require_once __DIR__ . "/internals/Utils.php";
require_once __DIR__ . "/internals/Exceptions.php";

spl_autoload_register(Utils\autoload(__NAMESPACE__, __DIR__ . "/classes"));

function exclude($path)
{
    $GLOBALS[Preprocessing\EXCLUDED_PATHS][] = $path;
}

function filter($subject, $filter)
{
    list($class, $method) = Utils\parseCallback($subject);
    if (is_array($subject) && is_object(reset($subject))) {
        $filter = chain(requireInstance(reset($subject)), $filter);
    }
    return Filtering\register($class, $method, $filter);
}

function dismiss(array $filterHandle)
{
    Filtering\unregister($filterHandle);
}

function chain()
{
    $chain = func_get_args();
    return function(Call $call) use ($chain) {
        try {
            foreach ($chain as $filter) {
                call_user_func($filter, $call);
            }
        } catch (Exceptions\SignalToBreakFilterChain $e) {
            return;
        }
    };
}

function breakChain()
{
    throw new Exceptions\SignalToBreakFilterChain;
}

function returnValue($value = null)
{
    return function(Call $call) use ($value) {
        $call->complete($value);
    };
}

function setArgs(array $values)
{
    return function(Call $call) use ($values) {
        foreach ($values as $offset => $value) {
            if ($value instanceof Reference) {
                $value = &$value->get();
            }
            $call->args[$offset] = $value;
        }
    };
}

function requireArgs()
{
    $arguments = func_get_args();
    return function(Call $call) use ($arguments) {
        foreach ($arguments as $offset => $argument) {
            if ($call->args[$offset] !== $argument) {
                breakChain();
            }
        }
    };
}

function requireInstance($instance)
{
    return function(Call $call) use ($instance) {
        if ($call->object !== $instance) {
            breakChain();
        }
    };
}

function requireUncompleted()
{
    return function(Call $call) {
        if ($call->isCompleted()) {
            breakChain();
        }
    };
}

function assertCompleted()
{
    return function(Call $call) {
        if (!$call->isCompleted()) {
            throw new Exceptions\UnexpectedUncompletedCall;
        }
    };
}

function dispatchNext()
{
    return function(Call $call) {
        $next = $call->next();
        Filtering\dispatch($next);
        if ($next->isCompleted()) {
            $call->complete($next->getRawResult());
        }
    };
}

function say($string)
{
    return function() use ($string) {
        echo $string;
    };
}

function expectCalls($min, $max = null)
{
    $call = Call::top();
    $origin = $call->file . ":" . $call->line;
    return new CallCountExpectation($min, (($max !== null) ? $max : $min), $origin);
}

$GLOBALS[Preprocessing\PREPROCESSORS] = array(
    Preprocessing\prependCodeToFunctions(Utils\condense(Splices\CALL_FILTERING_SPLICE)),
    Preprocessing\replaceTokens(T_EVAL, Splices\EVAL_REPLACEMENT_SPLICE),
);

Preprocessing\Stream::wrap();
