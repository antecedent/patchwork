<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

use Patchwork\Utils;

abstract class Exception extends \Exception
{
}

class NoResult extends Exception
{
}

class StackEmpty extends Exception
{
    protected $message = "There are no calls in the dispatch stack";
}

abstract class CallbackException extends Exception
{
    function __construct($callback)
    {
        parent::__construct(sprintf($this->message, Utils\callableToString($callback)));
    }
}

class NotUserDefined extends CallbackException
{
    protected $message = "%s is not a user-defined function or method";
}

class DefinedTooEarly extends CallbackException
{

    function __construct($callback)
    {
        $this->message = "The file that defines %s() was included earlier than Patchwork. " .
                         "This is likely a result of an improper setup; see readme for details.";
        parent::__construct($callback);
    }
}

class ConfigException extends Exception
{
}

class ConfigMalformed extends ConfigException
{
    function __construct($file, $message)
    {
        parent::__construct(sprintf(
            'The configuration file %s is malformed: %s',
            $file,
            $message
        ));
    }
}

class ConfigKeyNotRecognized extends ConfigException
{
    function __construct($key, $list, $file)
    {
        parent::__construct(sprintf(
            "The key '%s' in the configuration file %s was not recognized. " .
            "You might have meant one of these: %s",
            $key,
            $file,
            join(', ', $list)
        ));
    }
}
