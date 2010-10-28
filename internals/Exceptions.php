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

class CallResumed extends Exception
{
}

abstract class CallbackException extends Exception
{
    function __construct($callback)
    {
        parent::__construct(sprintf($this->message, Utils\callbackToString($callback)));
    }
}

class NotImplemented extends CallbackException
{
    protected $message = "%s is not implemented";
}

class NotDefined extends CallbackException
{
    protected $message = "%s is not defined";
}

class NotPreprocessed extends CallbackException
{
    protected $message = "%s is not defined in a preprocessed file";
}
