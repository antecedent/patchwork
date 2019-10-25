<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
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
    protected $message = 'Please include {"redefinable-internals": ["%s"]} in your patchwork.json.';
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

class InternalMethodsNotSupported extends CallbackException
{
    protected $message = "Methods of internal classes (such as %s) are not yet redefinable in Patchwork 2.1.";
}

class InternalsNotSupportedOnHHVM extends CallbackException
{
    protected $message = "As of version 2.1, Patchwork cannot redefine internal functions and methods (such as %s) on HHVM.";
}

class CachePathUnavailable extends Exception
{
    function __construct($location)
    {
        parent::__construct(sprintf(
            "The specified cache path is inexistent or read-only: %s",
            $location
        ));
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

class CachePathConflict extends ConfigException
{
    function __construct($first, $second)
    {
        parent::__construct(sprintf(
            "Detected configuration files provide conflicting cache paths: %s and %s",
            $first,
            $second
        ));
    }
}

class NewKeywordNotRedefinable extends ConfigException
{
    protected $message = 'Please set {"new-keyword-redefinable": true} to redefine instantiations';
}

class NonNullToVoid extends Exception
{
	protected $message = 'A redefinition of a void-typed callable attempted to return a non-null result';
}
