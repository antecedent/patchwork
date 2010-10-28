<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Patches;

use Patchwork\Utils;
use Patchwork\Exceptions;
use Patchwork\Preprocessor;

const CALLBACKS  = 'Patchwork\Patches\CALLBACKS';
const CALL_STACK = 'Patchwork\Patches\CALL_STACK';

const HANDLE_REFERENCE_OFFSET = 3;
const EVALUATED_CODE_FILE_NAME_SUFFIX = " : eval'd code";

function register($function, $patch)
{
	assertPatchable($function);
	list($class, $method) = Utils\parseCallback($function);
	$patches = &$GLOBALS[CALLBACKS][$class][$method];
	$offset = Utils\append($patches, $patch);
	return array($class, $method, $offset, &$patches[$offset]);
}

function unregister(array $handle)
{
	list($class, $method, $offset) = $handle;
	$reference = &$handle[HANDLE_REFERENCE_OFFSET];
	if (isset($reference)) {
		$reference = null;
		unset($GLOBALS[CALLBACKS][$class][$method][$offset]);
	}
}

function assertPatchable($function)
{
	try {
		$reflection = Utils\reflectCallback($function);
	} catch (\ReflectionException $e) {
		throw new Exceptions\NotDefined($function);
	}
	$file = Utils\chop($reflection->getFileName(), EVALUATED_CODE_FILE_NAME_SUFFIX);
	if (!Preprocessor\hasPreprocessed($file)) {
		throw Exceptions\NotPreprocessed($function);
	}
}

function bindToInstance($instance, $patch)
{
	return function() use ($instance, $patch) {
		if (getCallProperty("object") !== $instance) {
			throw new Exceptions\ResumedCall;
		}
		return execute($patch);
	};
}

function execute($patch)
{
	$arguments = getCallProperty("args");
	$parameters = Utils\reflectCallback($patch)->getParameters();
	foreach ($arguments as $offset => $argument) {
		if (isset($parameters[$offset]) && !$parameters[$offset]->isPassedByReference()) {
			$arguments[$offset] = $argument;
		}
 	}
	return call_user_func_array($patch, $arguments);
}

function traceCall()
{
	if (empty($GLOBALS[CALL_STACK])) {
		throw new Exceptions\NoCallBeingHandled;
	}
	return reset($GLOBALS[CALL_STACK]);
}

function getCallProperties()
{
	$trace = traceCall();
	return reset($trace);
}

function getCallProperty($property)
{
	$frame = getCallProperties();
	return isset($frame[$property]) ? $frame[$property] : null;
}

function handle($class, $method, $trace, &$result)
{
	$GLOBALS[CALL_STACK][] = $trace;
	$exception = null;
	$resultReceived = false;
	try {
		foreach ($GLOBALS[CALLBACKS][$class][$method] as $patch) {
			try {
				$result = execute($patch);
				$resultReceived = true;
			} catch (Exceptions\ResumedCall $e) {
				continue;
			}
		}
	} catch (\Exception $e) {
		$exception = $e;
	}
	array_pop($GLOBALS[CALL_STACK]);
	if (isset($exception)) {
		throw $exception;
	}
	return $resultReceived;
}

$GLOBALS[CALLBACKS] = $GLOBALS[CALL_STACK] = array();
