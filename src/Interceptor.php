<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

require __DIR__ . "/Interceptor/PatchHandle.php";
require __DIR__ . "/Interceptor/MethodPatchDecorator.php";

use Patchwork\Utils;
use Patchwork\Stack;
use Patchwork\Exceptions;

const EVALUATED_CODE_FILE_NAME_SUFFIX = "/\(\d+\) : eval\(\)'d code$/";

function patch($function, $patch)
{
    assertPatchable($function);
    list($class, $method) = Utils\interpretCallback($function);
    if (empty($class)) {
        $handle = patchFunction($method, $patch);
    } else {
        if (Utils\callbackTargetDefined($function)) {
            $handle = patchMethod($function, $patch);
        } else {
            $handle = scheduleMethodPatch($function, $patch);
        }
    }
    attachExistenceAssertion($handle, $function);
    return $handle;
}

function attachExistenceAssertion(PatchHandle $handle, $target)
{
    $handle->addExpirationHandler(function() use ($target) {
        if (!Utils\callbackTargetDefined($target)) {
            # Not using exceptions because this might happen during PHP shutdown
            $message = "%s was never defined during the lifetime of its redefinition";
            trigger_error(sprintf($message, Utils\callbackToString($target)), E_USER_WARNING);
        }
    });
}

function assertPatchable($function)
{
    if (!Utils\callbackTargetDefined($function)) {
        return;
    }
    $reflection = Utils\reflectCallback($function);
    if ($reflection->isInternal()) {
        throw new Exceptions\NotUserDefined($function);
    }
    $file = $reflection->getFileName();
    if (Utils\runningOnHHVM()) {
        return;
    }
    $evaluated = preg_match(EVALUATED_CODE_FILE_NAME_SUFFIX, $file);
    if (!$evaluated && empty(State::$preprocessedFiles[$file])) {
        throw new Exceptions\DefinedTooEarly($function);
    }
}

function patchFunction($function, $patch)
{
    $handle = new PatchHandle;
    $patches = &State::$patches[null][$function];
    $offset = Utils\append($patches, array($patch, $handle));
    $handle->addReference($patches[$offset]);
    if (Utils\runningOnHHVM()) {
        patchOnHHVM($function, $patch, $handle);
    }
    return $handle;
}

function scheduleMethodPatch($function, $patch)
{
    $handle = new PatchHandle;
    $scheduledPatch = array($function, $patch, $handle);
    $offset = Utils\append(State::$scheduledPatches, $scheduledPatch);
    $handle->addReference(State::$scheduledPatches[$offset]);
    return $handle;
}

function applyScheduledPatches()
{
    foreach (State::$scheduledPatches as $offset => $scheduledPatch) {
        if (empty($scheduledPatch)) {
            unset(State::$scheduledPatches[$offset]);
            continue;
        }
        list($function, $patch, $handle) = $scheduledPatch;
        if (Utils\callbackTargetDefined($function)) {
            assertPatchable($function, false);
            patchMethod($function, $patch, $handle);
            unset(State::$scheduledPatches[$offset]);
        }
    }
}

function patchMethod($function, $patch, PatchHandle $handle = null)
{
    if ($handle === null) {
        $handle = new PatchHandle;
    }
    list($class, $method, $instance) = Utils\interpretCallback($function);
    $patch = new MethodPatchDecorator($patch);
    $patch->superclass = $class;
    $patch->method = $method;
    $patch->instance = $instance;
    $reflection = new \ReflectionMethod($class, $method);
    $declaringClass = $reflection->getDeclaringClass();
    $class = $declaringClass->getName();
    if (Utils\traitsSupported()) {
        $aliases = $declaringClass->getTraitAliases();
        if (isset($aliases[$method])) {
            list($trait, $method) = explode("::", $aliases[$method]);
        }
    }
    $patches = &State::$patches[$class][$method];
    $offset = Utils\append($patches, array($patch, $handle));
    $handle->addReference($patches[$offset]);
    if (Utils\runningOnHHVM()) {
        patchOnHHVM("$class::$method", $patch, $handle);
    }
    return $handle;
}

function unpatchAll()
{
    foreach (State::$patches as $class => $patchesByClass) {
        foreach ($patchesByClass as $method => $patches) {
            foreach ($patches as $patch) {
                list($callback, $handle) = $patch;
                $handle->removePatches();
            }
        }
    }
    State::$patches = array();
}

function runPatch($patch)
{
    return call_user_func_array($patch, Stack\top("args"));
}

function intercept($class, $calledClass, $method, $frame, &$result, array $args = null)
{
    $success = false;
    Stack\pushFor($frame, $calledClass, function() use ($class, $method, &$result, &$success) {
        foreach (State::$patches[$class][$method] as $offset => $patch) {
            if (empty($patch)) {
                unset(State::$patches[$class][$method][$offset]);
                continue;
            }
            State::$patchStack[] = array($class, $method, $offset);
            try {
                $result = runPatch(reset($patch));
                $success = true;
            } catch (Exceptions\NoResult $e) {
                array_pop(State::$patchStack);
                continue;
            }
            array_pop(State::$patchStack);
        }
    }, $args);
    return $success;
}

function callOriginal(array $args = null)
{
    list($class, $method, $offset) = end(State::$patchStack);
    $patch = &State::$patches[$class][$method][$offset];
    $backup = $patch;
    $patch = array('Patchwork\fallBack', new PatchHandle);
    $top = Stack\top();
    if ($args === null) {
        $args = $top["args"];
    }
    try {
        if (isset($top["class"])) {
            $reflection = new \ReflectionMethod(Stack\topCalledClass(), $top["function"]);
            $reflection->setAccessible(true);
            $result = $reflection->invokeArgs(Stack\top("object"), $args);
        } else {
            $result = call_user_func_array($top["function"], $args);
        }
    } catch (\Exception $e) {
        $exception = $e;
    }
    $patch = $backup;
    if (isset($exception)) {
        throw $exception;
    }
    return $result;
}

function patchOnHHVM($function, $patch, PatchHandle $handle)
{
    fb_intercept($function, function($name, $obj, $args, $data, &$done) use ($patch) {
        list($class, $method) = Utils\interpretCallback($name);
        $calledClass = null;
        if (is_string($obj)) {
            $calledClass = $obj;
        } elseif (is_object($obj)) {
            $calledClass = get_class($obj);
        }
        $frame = count(debug_backtrace(false)) - 1;
        $done = intercept($class, $calledClass, $method, $frame, $result, $args);
    });
    $handle->addExpirationHandler(getHHVMExpirationHandler($function));
}

function getHHVMExpirationHandler($function)
{
    return function() use ($function) {
        list($class, $method) = Utils\interpretCallback($function);
        $empty = true;
        foreach (State::$patches[$class][$method] as $offset => $patch) {
            if (!empty($patch)) {
                $empty = false;
                break;
            } else {
                unset(State::$patches[$class][$method][$offset]);
            }
        }
        if ($empty) {
            fb_intercept($function, null);
        }
    };
}

class State
{
    static $patches = array();
    static $scheduledPatches = array();
    static $preprocessedFiles = array();
    static $patchStack = array();
}
