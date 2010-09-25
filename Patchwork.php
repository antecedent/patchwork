<?php

namespace Patchwork;

require_once __DIR__ . '/modules/Exceptions.php';
require_once __DIR__ . '/modules/Common.php';
require_once __DIR__ . '/modules/Patches.php';
require_once __DIR__ . '/modules/Preprocessing.php';
require_once __DIR__ . '/modules/Tokens.php';

spl_autoload_register(Common\autoload(__NAMESPACE__, __DIR__ . '/classes'));

const FILTERS = 'Patchwork\FILTERS';
const DISPATCH_STACK = 'Patchwork\DISPATCH_STACK';

function enable($paths = ".*")
{
    if (empty($paths)) {
        throw new Exceptions\NoPathsProvided;
    }
    $GLOBALS[Preprocessing\PATHS] = array();
    foreach ((array) $paths as $path) {
        $GLOBALS[Preprocessing\PATHS][] = '<^' . $path . '$>';
    }
}

function disable()
{
    $paths = $GLOBALS[Preprocessing\PATHS];
    $GLOBALS[Preprocessing\PATHS] = array();
    return $paths;
}

function is_enabled()
{
    return !empty($GLOBALS[Preprocessing\PATHS]);
}

function must_be_enabled()
{
    if (!is_enabled()) {
        throw new Exceptions\NotEnabled;
    }
}

function filter($subject, $filter)
{
    must_be_enabled();
    list($class, $function) = Common\parse_callback($subject);
    $filters = &$GLOBALS[FILTERS][$class][$function];    
    $offset = Common\append($filters, $filter);
    if (is_array($subject) && is_object(reset($subject))) {
        $filters[$offset] = function(Call $call) use ($filter, $subject) {
            match_instance(reset($subject));
            return call_user_func($filter, $call);
        };
    }    
    return array($class, $function, $offset);
}

function dismiss(array $filter_offsets)
{
    must_be_enabled();
    if (is_array(reset($filter_offsets))) {
        foreach ($filter_offsets as $argument) {
            dismiss($argument);
        }
        return;
    }
    if (!empty($filter_offsets)) {
        list($class, $function, $offset) = $filter_offsets;
        unset($GLOBALS[FILTERS][$class][$function][$offset]);
    }
}

function expect($calls, $subject, $filter)
{
    list($min, $max) = is_array($calls) ? $calls : array($calls, $calls);
    $filter = new CallCountExpectation($filter, $subject, $min, $max);
    return filter($subject, $filter);
}

function forbid($subject)
{
    return expect(0, $subject, null);
}

function dispatch(Call $call)
{
    $GLOBALS[DISPATCH_STACK][] = $call;
    try {
        $result = execute_filters($call);
        array_pop($GLOBALS[DISPATCH_STACK]);
        return $result;
    } catch (\Exception $e) {
        array_pop($GLOBALS[DISPATCH_STACK]);
        throw $e;
    }
}

function execute_filters(Call $call)
{
    $result = null;
    foreach ($GLOBALS[FILTERS][$call->class][$call->function] as $filter) {
        try {
            $new_result = call_user_func($filter, $call);
            if ($new_result !== null && !$new_result instanceof Result) {
                throw new Exceptions\InvalidFilterReturnValue($new_result);
            }            
        } catch (Exceptions\SkippedFilter $e) {
            $new_result = null;
        }
        if ($result && $new_result) {
            throw new Exceptions\FilterConflict($call);
        }
        if (!$result) {
            $result = $new_result;
        }
    }
    return $result;
}

function top()
{
    return end($GLOBALS[DISPATCH_STACK]);
}

function value($value = null)
{
    return function() use ($value) {
        return result($value);
    };
}

function result($result = null)
{
    return new Result($result);
}

function skip()
{
    throw new Exceptions\SkippedFilter;
}

function forward_magic_calls($class, array $methods = array("__call", "__callStatic"))
{
    $filters = array();
    foreach ($methods as $method) {
        $filters[] = filter(array($class, $method), function(Call $call) {
            try {
                return dispatch($call->next());
            } catch (EmptyBacktrace $e) {
                return;
            }
        });
    }
    return $filters;
}

function match_instance($instance)
{
    if (top()->object !== $instance) {
        skip();
    }
}

$GLOBALS[FILTERS] = $GLOBALS[DISPATCH_STACK] = array();

$GLOBALS[Preprocessing\PREPROCESSORS] = array(
    Preprocessing\prepend_code_to_functions(Common\condense(Patches\CALL_FILTERING_PATCH)),
    Preprocessing\replace_tokens(T_EVAL, Patches\EVAL_REPLACEMENT_PATCH),
);

Preprocessing\Stream::wrap();
