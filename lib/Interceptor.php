<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2014 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

require __DIR__ . "/Interceptor/PatchHandle.php";
require __DIR__ . "/Interceptor/MethodPatchDecorator.php";

use Patchwork;
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
    return $handle;
}

function unpatchAll()
{
    State::$patches = array();
}

function runPatch($patch)
{
    return call_user_func_array($patch, Stack\top("args"));
}

function intercept($class, $calledClass, $method, $frame, &$result)
{
    $success = false;
    Stack\pushFor($frame, $calledClass, function() use ($class, $method, &$result, &$success) {
        foreach (State::$patches[$class][$method] as $offset => $patch) {
            if (empty($patch)) {
                unset(State::$patches[$class][$method][$offset]);
                continue;
            }
            try {
                $result = runPatch(reset($patch));
                $success = true;
            } catch (Exceptions\NoResult $e) {
                continue;
            }
        }
    });
    return $success;
}

class State
{
    static $patches = array();
    static $scheduledPatches = array();
    static $preprocessedFiles = array();
}