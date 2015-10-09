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
            $handle = queueMethodPatch($function, $patch);
            if (Utils\runningOnHHVM()) {
                patchOnHHVM("$class::$method", $handle);
            }
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
        patchOnHHVM($function, $handle);
    }
    return $handle;
}

function queueMethodPatch($function, $patch)
{
    $handle = new PatchHandle;
    $queuedPatch = array($function, $patch, $handle);
    $offset = Utils\append(State::$queuedPatches, $queuedPatch);
    $handle->addReference(State::$queuedPatches[$offset]);
    return $handle;
}

function deployQueue()
{
    foreach (State::$queuedPatches as $offset => $queuedPatch) {
        if (empty($queuedPatch)) {
            unset(State::$queuedPatches[$offset]);
            continue;
        }
        list($function, $patch, $handle) = $queuedPatch;
        if (Utils\callbackTargetDefined($function)) {
            assertPatchable($function, false);
            patchMethod($function, $patch, $handle);
            unset(State::$queuedPatches[$offset]);
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
    if (!Utils\runningOnHHVM() && Utils\traitsSupported()) {
        $aliases = $declaringClass->getTraitAliases();
        if (isset($aliases[$method])) {
            list($trait, $method) = explode("::", $aliases[$method]);
        }
    }
    $patches = &State::$patches[$class][$method];
    $offset = Utils\append($patches, array($patch, $handle));
    $handle->addReference($patches[$offset]);
    if (Utils\runningOnHHVM()) {
        patchOnHHVM("$class::$method", $handle);
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
        foreach (getPatchesFor($class, $method) as $offset => $patch) {
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

function patchOnHHVM($function, PatchHandle $handle)
{
    fb_intercept($function, function($name, $obj, $args, $data, &$done) {
        deployQueue();
        list($class, $method) = Utils\interpretCallback($name);
        $calledClass = null;
        if (is_string($obj)) {
            $calledClass = $obj;
        } elseif (is_object($obj)) {
            $calledClass = get_class($obj);
        }
        $frame = count(debug_backtrace(false)) - 1;
        $result = null;
        $done = intercept($class, $calledClass, $method, $frame, $result, $args);
        return $result;
    });
    $handle->addExpirationHandler(getHHVMExpirationHandler($function));
}

function getHHVMExpirationHandler($function)
{
    return function() use ($function) {
        list($class, $method) = Utils\interpretCallback($function);
        $empty = true;
        foreach (getPatchesFor($class, $method) as $offset => $patch) {
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

function getPatchesFor($class, $method)
{
    if (!isset(State::$patches[$class][$method])) {
        return array();
    }
    return State::$patches[$class][$method];
}

class State
{
    static $patches = array();
    static $queuedPatches = array();
    static $preprocessedFiles = array();
    static $patchStack = array();
}
