<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork;

require_once __DIR__ . "/internals/Filtering.php";
require_once __DIR__ . "/internals/Preprocessing.php";
require_once __DIR__ . "/internals/Splices.php";
require_once __DIR__ . "/internals/Tokens.php";
require_once __DIR__ . "/internals/Utils.php";
require_once __DIR__ . "/internals/Exceptions.php";

spl_autoload_register(Utils\autoload(__NAMESPACE__, __DIR__ . "/classes"));

/**
 * Adds the argument string to the preprocessing blacklist. This tells Patchwork not to preprocess
 * any files whose absolute paths begin with this string.
 */
function exclude($path)
{
    $GLOBALS[Preprocessing\BLACKLIST][] = $path;
}

/**
 * Attaches the given filter to the specified subject (function or method). Both the subject and
 * the filter have to be valid PHP callbacks.
 */
function filter($subject, $filter)
{
    list($class, $method) = Utils\parseCallback($subject);
    if (is_array($subject) && is_object(reset($subject))) {
        $filter = chain(requireInstance(reset($subject)), $filter);
    }
    return Filtering\register($class, $method, $filter);
}

/**
 * Takes a result of a call to Patchwork\filter() and detaches the filter attached by that call. 
 */
function dismiss(array $filterHandle)
{
    Filtering\unregister($filterHandle);
}

/**
 * Combines multiple filters to a single filter, which executes the provided filters in the
 * original order and stops when a SignalToBreakFilterChain is thrown. The filters to be combined
 * must be passed as separate arguments to this funciton.
 */
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

/**
 * Throws a SignalToBreakFilterChain.
 */
function breakChain()
{
    throw new Exceptions\SignalToBreakFilterChain;
}

/**
 * Returns a filter that unconditionally completes the filtered call with the given return value.
 */
function returnValue($value = null)
{
    return function(Call $call) use ($value) {
        $call->complete($value);
    };
}

/**
 * Returns a filter that assigns the provided values (indexed by argument offset, zero-based) to
 * the arguments of the filtered call.
 */
function assignArgs(array $values)
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

/**
 * Returns a filter that breaks the current filter chain unless all values in the provided array
 * (indexed by argument offset, zero-indexed) are identical to the matching arguments of the 
 * filtered call.
 */
function requireArgs(array $arguments)
{
    return function(Call $call) use ($arguments) {
        foreach ($arguments as $offset => $argument) {
            if ($call->args[$offset] !== $argument) {
                breakChain();
            }
        }
    };
}

/**
 * Returns a filter that breaks the current filter chain unless the object which receives the
 * filtered call is identical to the provided one.
 */
function requireInstance($instance)
{
    return function(Call $call) use ($instance) {
        if ($call->object !== $instance) {
            breakChain();
        }
    };
}

/**
 * Returns a filter that breaks the current filter chain unless the currently filtered call is
 * completed.
 */
function requireUncompleted()
{
    return function(Call $call) {
        if ($call->isCompleted()) {
            breakChain();
        }
    };
}

/**
 * Returns a filter that throws an exception if the currently filtered call is not completed.
 */
function assertCompleted()
{
    return function(Call $call) {
        if (!$call->isCompleted()) {
            throw new Exceptions\UnexpectedUncompletedCall;
        }
    };
}

/**
 * Returns a filter that takes the call which follows the currently dispatched one in the call 
 * stack and filters it, merging the result (if any) into the original call.
 */
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

/**
 * Returns a filter that prints the provided string on each filtered call.
 */
function say($string)
{
    return function() use ($string) {
        echo $string;
    };
}

/**
 * Returns a filter that expects a number of calls that falls into the specified range. When the
 * maximum call count is not specified, it defaults to the minimum. If the expectation is not met,
 * an exception is thrown. Note, however, that this may not occur until all references to the
 * filter have been lost.
 */
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

exclude(__DIR__ . "/classes/");

Preprocessing\Stream::wrap();
