<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

use Patchwork;
use Patchwork\Utils;
use Patchwork\Exceptions;
use Patchwork\Stack;

const HANDLE_REFERENCE_OFFSET = 3;
const EVALUATED_CODE_FILE_NAME_SUFFIX = "/\(\d+\) : eval\(\)'d code$/";

function patch($function, $patch, $allowUndefined = false)
{
    assertPatchable($function, $allowUndefined);
    list($class, $method) = Utils\parseCallback($function);
    if (is_array($function) && is_object(reset($function))) {
        $patch = bindPatchToInstance(reset($function), $patch);
    }    
    $patches = &State::$patches[$class][$method];
    $offset = Utils\append($patches, $patch);
    return array($class, $method, $offset, &$patches[$offset]);
}

function unpatch(array $handle)
{
    list($class, $method, $offset) = $handle;
    $reference = &$handle[HANDLE_REFERENCE_OFFSET];
    if (isset($reference)) {
        $reference = null;
        unset(State::$patches[$class][$method][$offset]);
    }
}

function unpatchAll()
{
    State::$patches = array();
}

function assertPatchable($function, $allowUndefined = false)
{
    try {
        $reflection = Utils\reflectCallback($function);
    } catch (\ReflectionException $e) {
        if (!$allowUndefined) {
            throw new Exceptions\NotDefined($function);
        }
        return;
    }
    if ($reflection->isInternal()) {
        throw new Exceptions\NotUserDefined($function);
    }
    $file = $reflection->getFileName();
    $evaluated = preg_match(EVALUATED_CODE_FILE_NAME_SUFFIX, $file);
    if (!$evaluated && empty(State::$preprocessedFiles[$file])) {
        throw new Exceptions\DefinedTooEarly($function);
    }
}

function runPatch($patch)
{
    return call_user_func_array($patch, Stack\top("args"));
}

function bindPatchToInstance($instance, $patch)
{
    return function() use ($instance, $patch) {
        if (Stack\top("object") !== $instance) {
            Patchwork\shift();
        }
        return runPatch($patch);
    };
}

function intercept($class, $method, $frame, &$result)
{
    $success = false;
    Stack\pushFor($frame, function() use ($class, $method, &$result, &$success) {
        foreach (State::$patches[$class][$method] as $patch) {
            try {
                $result = runPatch($patch);
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
    static $preprocessedFiles = array();
}
