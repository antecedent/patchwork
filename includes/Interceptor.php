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
use Patchwork\Preprocessor;
use Patchwork\Stack;

const PATCHES = 'Patchwork\Interceptor\PATCHES';
const PREPROCESSED_FILES = 'Patchwork\Interceptor\PATCHABLE_FILES';

const HANDLE_REFERENCE_OFFSET = 3;
const EVALUATED_CODE_FILE_NAME_SUFFIX = "/\(\d+\) : eval\(\)'d code$/";

function patch($function, $patch, $shouldVerify = true)
{
    if ($shouldVerify) {
        assertPatchable($function);
    }
    list($class, $method) = Utils\parseCallback($function);
    if (is_array($function) && is_object(reset($function))) {
        $patch = bindPatchToInstance(reset($function), $patch);
    }    
    $patches = &$GLOBALS[PATCHES][$class][$method];
    $offset = Utils\append($patches, $patch);
    return array($class, $method, $offset, &$patches[$offset]);
}

function unpatch(array $handle)
{
    list($class, $method, $offset) = $handle;
    $reference = &$handle[HANDLE_REFERENCE_OFFSET];
    if (isset($reference)) {
        $reference = null;
        unset($GLOBALS[PATCHES][$class][$method][$offset]);
    }
}

function assertPatchable($function)
{
    try {
        $reflection = Utils\reflectCallback($function);
    } catch (\ReflectionException $e) {
        throw new Exceptions\NotDefined($function);
    }
    $file = $reflection->getFileName();
    $evaluated = preg_match(EVALUATED_CODE_FILE_NAME_SUFFIX, $file);
    if (!$evaluated && empty($GLOBALS[PREPROCESSED_FILES][$file])) {
        throw new Exceptions\NotPreprocessed($function);
    }
}

function runPatch($patch)
{
    return Utils\callBySignature($patch, Stack\top("args"));
}

function bindPatchToInstance($instance, $patch)
{
    return function() use ($instance, $patch) {
        if (Stack\top("object") !== $instance) {
            Patchwork\escape();
        }
        return runPatch($patch);
    };
}

function intercept($class, $method, $frame, &$result)
{
    $success = false;
    Stack\pushFor($frame, function() use ($class, $method, &$result, &$success) {
        foreach ($GLOBALS[PATCHES][$class][$method] as $patch) {
            try {
                $result = runPatch($patch);
                $success = true;
            } catch (Exceptions\PatchEscaped $e) {
                continue;
            }
        }
    });
    return $success;
}

$GLOBALS[PATCHES] = $GLOBALS[PREPROCESSED_FILES] = array();
