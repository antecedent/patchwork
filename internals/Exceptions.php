<?php

namespace Patchwork\Exceptions;

use Patchwork\Utils;
use Patchwork\Call;

interface Exception
{
}

abstract class LogicException extends \LogicException implements Exception
{
}

abstract class RuntimeException extends \RuntimeException implements Exception
{
}

class EmptyBacktrace extends RuntimeException
{
    protected $message = "Cannot shift a stack frame from an empty backtrace";
}

class MultipleSourceSplices extends LogicException
{
    protected $message = "Multiple splices at the same point of the source are not possible";
}

class MultipleCallCompletions extends LogicException
{
    protected $message = "Cannot complete the same call more than once";
}

class CallResultUnavailable extends RuntimeException
{
    protected $message = "Cannot retrieve the result from an uncompleted call";
}

class NotImplemented extends LogicException
{
    function __construct($function)
    {
        parent::__construct("$function is not implemented");
    }
}

class UnexpectedUncompletedCall extends RuntimeException
{
    protected $message = "Unexpected uncompleted call (see the backtrace)";
}

class UnmetCallCountExpectation extends RuntimeException
{
    function __construct($calls, $min, $max, $origin)
    {
        parent::__construct(sprintf(
            "Unmet call count expectation: %s expected, %d received (set in %s)",
            Utils\rangeToReadableString($min, $max), $calls, $origin
        ));
    }
}

class IllegalFilterResult extends LogicException
{
    protected $message = "Non-null filter result received";
}

abstract class Signal extends \Exception implements Exception
{
}

class SignalToBreakFilterChain extends Signal
{
}
