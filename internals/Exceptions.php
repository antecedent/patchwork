<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Exceptions;

use Patchwork\Utils;
use Patchwork\Call;

abstract class Exception extends \Exception
{
}

class EmptyBacktrace extends Exception
{
    protected $message = "Cannot shift a stack frame from an empty backtrace";
}

class CallAlreadyCompleted extends Exception
{
    protected $message = "Cannot complete the same call more than once";
}

class CallResultUnavailable extends Exception
{
    protected $message = "Cannot retrieve the result from an uncompleted call";
}

class NotImplemented extends Exception
{
    function __construct($function)
    {
        parent::__construct("$function is not implemented");
    }
}

class UnexpectedUncompletedCall extends Exception
{
    protected $message = "Unexpected uncompleted call (see the backtrace)";
}

class UnmetCallCountExpectation extends Exception
{
    function __construct($calls, $min, $max, $origin)
    {
        parent::__construct(sprintf(
            "Unmet call count expectation: %s expected, %d received (set in %s)",
            Utils\rangeToReadableString($min, $max), $calls, $origin
        ));
    }
}

class IllegalFilterResult extends Exception
{
    protected $message = "Non-null filter result received";
}

abstract class Signal extends Exception
{
}

class SignalToBreakFilterChain extends Signal
{
}
