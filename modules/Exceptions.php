<?php

namespace Patchwork\Exceptions;

use Patchwork\Common;
use Patchwork\Call;

interface Exception
{
}

class LogicException extends \LogicException implements Exception
{
}

class RuntimeException extends \RuntimeException implements Exception
{
}

class FilterConflict extends RuntimeException
{
    function __construct(Call $call)
    {
        parent::__construct(sprintf(
            "Multiple filters have provided a result for %s",
            $call->class ? ($call->class . "::" . $call->function) : $call->function
        ));
    }
}

class InvalidFilterReturnValue extends RuntimeException
{
    protected $message = "Neither NULL nor a Result object was returned by a filter (see backtrace
        for the actual value";
}

class NotEnabled extends LogicException
{
    protected $message = "Patchwork is not enabled";
}

class NoPathsProvided extends LogicException
{
    protected $message = 'No source paths were provided to Patchwork\enable()';
}

class EmptyBacktrace extends RuntimeException
{
    protected $message = "Cannot shift a stack frame from an empty backtrace";
}

class MultipleSourceSplices extends LogicException
{
    protected $message = "Multiple splices at the same point of the source are not possible";
}

class NotImplemented extends LogicException
{
    function __construct($function)
    {
        parent::__construct("$function is not implemented");
    }
}

class SkippedFilter extends RuntimeException
{
}

class UnmetCallCountExpectation extends RuntimeException
{
    function __construct($subject, $calls, $min, $max)
    {
        parent::__construct(sprintf(
            "Unmet call count expectation for %s: %d received, %s expected",
            Common\callback_to_string($subject), $calls, Common\range_to_string($min, $max)
        ));
    }
}
