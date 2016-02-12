<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CallRerouting;

require __DIR__ . '/CallRerouting/Handle.php';
require __DIR__ . '/CallRerouting/Decorator.php';

use Patchwork\Utils;
use Patchwork\Stack;
use Patchwork\Config;
use Patchwork\Exceptions;

const EVALUATED_CODE_FILE_NAME_SUFFIX = '/\(\d+\) : eval\(\)\'d code$/';

function connect($source, callable $target, Handle $handle = null)
{
    $handle = $handle ?: new Handle;
    list($class, $method) = Utils\interpretCallable($source);
    if (constitutesWildcard($source)) {
        return applyWildcard($source, $target, $handle);
    }
    if (Utils\isOwnName($class) || Utils\isOwnName($method)) {
        return $handle;
    }
    validate($source);
    if (empty($class)) {
        $handle = connectFunction($method, $target, $handle);
    } else {
        if (Utils\callableDefined($source)) {
            $handle = connectMethod($source, $target, $handle);
        } else {
            $handle = queueConnection($source, $target, $handle);
            if (Utils\runningOnHHVM()) {
                connectOnHHVM("$class::$method", $handle);
            }
        }
    }
    attachExistenceAssertion($handle, $source);
    return $handle;
}

function constitutesWildcard($source)
{
    $source = Utils\interpretCallable($source);
    $source = Utils\callableToString($source);
    return strcspn($source, '*{,}') != strlen($source);
}

function applyWildcard($wildcard, callable $target, Handle $handle = null)
{
    $handle = $handle ?: new Handle;
    list($class, $method, $instance) = Utils\interpretCallable($wildcard);
    if (!empty($instance)) {
        foreach (Utils\matchWildcard($method, get_class_methods($instance)) as $item) {
            if (!$handle->hasTag($item)) {
                connect([$instance, $item], $target, $handle);
                $handle->tag($item);
            }
        }
        return $handle;
    }

    $callables = Utils\matchWildcard($wildcard, Utils\getUserDefinedCallables());
    foreach ($callables as $callable) {
        if (!inPreprocessedFile($callable) || $handle->hasTag($callable)) {
            continue;
        }
        if (function_exists($callable)) {
            # Restore lower/upper case distinction
            $callable = (new \ReflectionFunction($callable))->getName();
        }
        connect($callable, $target, $handle);
        $handle->tag($callable);
    }
    if (!class_exists($class, false)) {
        queueConnection($wildcard, $target, $handle);
    }
    return $handle;
}

function attachExistenceAssertion(Handle $handle, $function)
{
    $handle->addExpirationHandler(function() use ($function) {
        if (!Utils\callableDefined($function)) {
            # Not using exceptions because this might happen during PHP shutdown
            $message = '%s() was never defined during the lifetime of its redefinition';
            trigger_error(sprintf($message, Utils\callableToString($function)), E_USER_WARNING);
        }
    });
}

function validate($function)
{
    if (!Utils\callableDefined($function)) {
        return;
    }
    $reflection = Utils\reflectCallable($function);
    if ($reflection->isInternal()) {
        throw new Exceptions\NotUserDefined($function);
    }
    if (Utils\runningOnHHVM()) {
        return;
    }
    if (!inPreprocessedFile($function) && Config\shouldWarnAbout($function)) {
        throw new Exceptions\DefinedTooEarly($function);
    }
}

function inPreprocessedFile($callable)
{
    if (Utils\runningOnHHVM()) {
        return true;
    }
    if (Utils\isOwnName(Utils\callableToString($callable))) {
        return false;
    }
    $file = Utils\reflectCallable($callable)->getFileName();
    $evaluated = preg_match(EVALUATED_CODE_FILE_NAME_SUFFIX, $file);
    return $evaluated || !empty(State::$preprocessedFiles[$file]);
}

function connectFunction($function, callable $target, Handle $handle = null)
{
    $handle = $handle ?: new Handle;
    $routes = &State::$routes[null][$function];
    $offset = Utils\append($routes, [$target, $handle]);
    $handle->addReference($routes[$offset]);
    if (Utils\runningOnHHVM()) {
        connectOnHHVM($function, $handle);
    }
    return $handle;
}

function queueConnection($source, callable $target, Handle $handle = null)
{
    $handle = $handle ?: new Handle;
    $offset = Utils\append(State::$queue, [$source, $target, $handle]);
    $handle->addReference(State::$queue[$offset]);
    return $handle;
}

function deployQueue()
{
    foreach (State::$queue as $offset => $item) {
        if (empty($item)) {
            unset(State::$queue[$offset]);
            continue;
        }
        list($source, $target, $handle) = $item;
        if (Utils\callableDefined($source) || constitutesWildcard($source)) {
            connect($source, $target, $handle);
            unset(State::$queue[$offset]);
        }
    }
}

function connectMethod($function, callable $target, Handle $handle = null)
{
    $handle = $handle ?: new Handle;
    list($class, $method, $instance) = Utils\interpretCallable($function);
    $target = new Decorator($target);
    $target->superclass = $class;
    $target->method = $method;
    $target->instance = $instance;
    $reflection = new \ReflectionMethod($class, $method);
    $declaringClass = $reflection->getDeclaringClass();
    $class = $declaringClass->getName();
    if (!Utils\runningOnHHVM()) {
        $aliases = $declaringClass->getTraitAliases();
        if (isset($aliases[$method])) {
            list($trait, $method) = explode('::', $aliases[$method]);
        }
    }
    $routes = &State::$routes[$class][$method];
    $offset = Utils\append($routes, [$target, $handle]);
    $handle->addReference($routes[$offset]);
    if (Utils\runningOnHHVM()) {
        connectOnHHVM("$class::$method", $handle);
    }
    return $handle;
}

function disconnectAll()
{
    foreach (State::$routes as $class => $routesByClass) {
        foreach ($routesByClass as $method => $routes) {
            foreach ($routes as $route) {
                list($callback, $handle) = $route;
                if ($handle !== null) {
                    $handle->expire();
                }
            }
        }
    }
    State::$routes = [];
}

function dispatchTo(callable $target)
{
    return call_user_func_array($target, Stack\top('args'));
}

function dispatch($class, $calledClass, $method, $frame, &$result, array $args = null)
{
    $success = false;
    Stack\pushFor($frame, $calledClass, function() use ($class, $method, &$result, &$success) {
        foreach (getRoutesFor($class, $method) as $offset => $route) {
            if (empty($route)) {
                unset(State::$routes[$class][$method][$offset]);
                continue;
            }
            State::$routeStack[] = [$class, $method, $offset];
            try {
                $result = dispatchTo(reset($route));
                $success = true;
            } catch (Exceptions\NoResult $e) {
                array_pop(State::$routeStack);
                continue;
            }
            array_pop(State::$routeStack);
            if ($success) {
                break;
            }
        }
    }, $args);
    return $success;
}

function relay(array $args = null)
{
    list($class, $method, $offset) = end(State::$routeStack);
    $route = &State::$routes[$class][$method][$offset];
    $backup = $route;
    $route = ['Patchwork\fallBack', new Handle];
    $top = Stack\top();
    if ($args === null) {
        $args = $top['args'];
    }
    try {
        if (isset($top['class'])) {
            $reflection = new \ReflectionMethod(Stack\topCalledClass(), $top['function']);
            $reflection->setAccessible(true);
            $result = $reflection->invokeArgs(Stack\top('object'), $args);
        } else {
            $result = call_user_func_array($top['function'], $args);
        }
    } catch (\Exception $e) {
        $exception = $e;
    }
    $route = $backup;
    if (isset($exception)) {
        throw $exception;
    }
    return $result;
}

function connectOnHHVM($function, Handle $handle)
{
    fb_intercept($function, function($name, $obj, $args, $data, &$done) {
        deployQueue();
        list($class, $method) = Utils\interpretCallable($name);
        $calledClass = null;
        if (is_string($obj)) {
            $calledClass = $obj;
        } elseif (is_object($obj)) {
            $calledClass = get_class($obj);
        }
        $frame = count(debug_backtrace(false)) - 1;
        $result = null;
        $done = dispatch($class, $calledClass, $method, $frame, $result, $args);
        return $result;
    });
    $handle->addExpirationHandler(getHHVMExpirationHandler($function));
}

function getHHVMExpirationHandler($function)
{
    return function() use ($function) {
        list($class, $method) = Utils\interpretCallable($function);
        $empty = true;
        foreach (getRoutesFor($class, $method) as $offset => $route) {
            if (!empty($route)) {
                $empty = false;
                break;
            } else {
                unset(State::$routes[$class][$method][$offset]);
            }
        }
        if ($empty) {
            fb_intercept($function, null);
        }
    };
}

function getRoutesFor($class, $method)
{
    if (!isset(State::$routes[$class][$method])) {
        return [];
    }
    return array_reverse(State::$routes[$class][$method], true);
}

class State
{
    static $routes = [];
    static $queue = [];
    static $preprocessedFiles = [];
    static $routeStack = [];
}
