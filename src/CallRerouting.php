<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CallRerouting;

require __DIR__ . '/CallRerouting/Handle.php';
require __DIR__ . '/CallRerouting/Decorator.php';

use Patchwork\Utils;
use Patchwork\Stack;
use Patchwork\Config;
use Patchwork\Exceptions;
use Patchwork\CodeManipulation;
use Patchwork\CodeManipulation\Actions\RedefinitionOfLanguageConstructs;
use Patchwork\CodeManipulation\Actions\RedefinitionOfNew;

const INTERNAL_REDEFINITION_NAMESPACE = 'Patchwork\Redefinitions';
const EVALUATED_CODE_FILE_NAME_SUFFIX = '/\(\d+\) : eval\(\)\'d code$/';
const INSTANTIATOR_NAMESPACE = 'Patchwork\Instantiators';
const INSTANTIATOR_DEFAULT_ARGUMENT = 'Patchwork\CallRerouting\INSTANTIATOR_DEFAULT_ARGUMENT';

const INTERNAL_STUB_CODE = '
    namespace @ns_for_redefinitions;
    function @name(@signature) {
        $__pwArgs = \array_slice(\debug_backtrace()[0]["args"], 1);
        if (!empty($__pwNamespace) && \function_exists($__pwNamespace . "\\\\@name")) {
            return \call_user_func_array($__pwNamespace . "\\\\@name", $__pwArgs);
        }
        @interceptor;
        return \call_user_func_array("@name", $__pwArgs);
    }
';

const INSTANTIATOR_CODE = '
    namespace @namespace;
    class @instantiator {
        function instantiate(@parameters) {
            $__pwArgs = \debug_backtrace()[0]["args"];
            foreach ($__pwArgs as $__pwOffset => $__pwValue) {
                if ($__pwValue === \Patchwork\CallRerouting\INSTANTIATOR_DEFAULT_ARGUMENT) {
                    unset($__pwArgs[$__pwOffset]);
                }
            }
            switch (count($__pwArgs)) {
                case 0:
                    return new \@class;
                case 1:
                    return new \@class($__pwArgs[0]);
                case 2:
                    return new \@class($__pwArgs[0], $__pwArgs[1]);
                case 3:
                    return new \@class($__pwArgs[0], $__pwArgs[1], $__pwArgs[2]);
                case 4:
                    return new \@class($__pwArgs[0], $__pwArgs[1], $__pwArgs[2], $__pwArgs[3]);
                case 5:
                    return new \@class($__pwArgs[0], $__pwArgs[1], $__pwArgs[2], $__pwArgs[3], $__pwArgs[4]);
                default:
                    $__pwReflector = new \ReflectionClass(\'@class\');
                    return $__pwReflector->newInstanceArgs($__pwArgs);
            }
        }
    }
';

function connect($source, callable $target, Handle $handle = null, $partOfWildcard = false)
{
    $source = translateIfLanguageConstruct($source);
    $handle = $handle ?: new Handle;
    list($class, $method) = Utils\interpretCallable($source);
    if (constitutesWildcard($source)) {
        return applyWildcard($source, $target, $handle);
    }
    if (Utils\isOwnName($class) || Utils\isOwnName($method)) {
        return $handle;
    }
    validate($source, $partOfWildcard);
    if (empty($class)) {
        if (Utils\callableDefined($source) && (new \ReflectionFunction($method))->isInternal()) {
            $stub = INTERNAL_REDEFINITION_NAMESPACE . '\\' . $source;
            return connect($stub, $target, $handle, $partOfWildcard);
        }
        $handle = connectFunction($method, $target, $handle);
    } else {
        if (Utils\callableDefined($source)) {
            if ($method === 'new') {
                $handle = connectInstantiation($class, $target, $handle);
            } elseif ((new \ReflectionMethod($class, $method))->isUserDefined()) {
                $handle = connectMethod($source, $target, $handle);
            } else {
                throw new InternalMethodsNotSupported($source);
            }
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

    $callables = Utils\matchWildcard($wildcard, Utils\getRedefinableCallables());
    foreach ($callables as $callable) {
        if (!inPreprocessedFile($callable) || $handle->hasTag($callable)) {
            continue;
        }
        if (function_exists($callable)) {
            # Restore lower/upper case distinction
            $callable = (new \ReflectionFunction($callable))->getName();
        }
        connect($callable, $target, $handle, true);
        $handle->tag($callable);
    }
    if (!isset($class) || !class_exists($class, false)) {
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

function validate($function, $partOfWildcard = false)
{
    list($class, $method) = Utils\interpretCallable($function);
    if (!Utils\callableDefined($function) || $method === 'new') {
        return;
    }
    $reflection = Utils\reflectCallable($function);
    $name = Utils\callableToString($function);
    if ($reflection->isInternal() && !in_array($name, Config\getRedefinableInternals())) {
        throw new Exceptions\NotUserDefined($function);
    }
    if (Utils\runningOnHHVM()) {
        if ($reflection->isInternal()) {
            throw new Exceptions\InternalsNotSupportedOnHHVM($function);
        }
        return;
    }
    if (!$reflection->isInternal() && !inPreprocessedFile($function) && !$partOfWildcard) {
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
    $reflection = Utils\reflectCallable($function);
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

function connectInstantiation($class, callable $target, Handle $handle = null)
{
    if (!Config\isNewKeywordRedefinable()) {
        throw new Exceptions\NewKeywordNotRedefinable;
    }
    $handle = $handle ?: new Handle;
    $class = strtr($class, ['\\' => '__']);
    $routes = &State::$routes["Patchwork\\Instantiators\\$class"]['instantiate'];
    $offset = Utils\append($routes, [$target, $handle]);
    $handle->addReference($routes[$offset]);
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
    connectDefaultInternals();
}

function dispatchTo(callable $target)
{
    return call_user_func_array($target, Stack\top('args'));
}

function dispatch($class, $calledClass, $method, $frame, &$result, array $args = null)
{
    $isInternalStub = strpos($method, INTERNAL_REDEFINITION_NAMESPACE) === 0;
    $isLanguageConstructStub = strpos($method, RedefinitionOfLanguageConstructs\LANGUAGE_CONSTRUCT_PREFIX) === 0;
    $isInstantiator = strpos($method, INSTANTIATOR_NAMESPACE) === 0;
    if ($isInternalStub && !$isLanguageConstructStub && $args === null) {
        # Mind the namespace-of-origin argument
        $trace = debug_backtrace();
        $args = array_reverse($trace)[$frame - 1]['args'];
        array_shift($args);
    }
    if ($isInstantiator) {
        $trace = debug_backtrace();
        $args = $args ?: array_reverse($trace)[$frame - 1]['args'];
        foreach ($args as $offset => $value) {
            if ($value === INSTANTIATOR_DEFAULT_ARGUMENT) {
                unset($args[$offset]);
            }
        }
    }
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
    $isInternalStub = strpos($method, INTERNAL_REDEFINITION_NAMESPACE) === 0;
    $isLanguageConstructStub = strpos($method, RedefinitionOfLanguageConstructs\LANGUAGE_CONSTRUCT_PREFIX) === 0;
    if ($isInternalStub && !$isLanguageConstructStub) {
        array_unshift($args, '');
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
        $frame = count(debug_backtrace(0)) - 1;
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

function dispatchDynamic($callable, array $arguments)
{
    list($class, $method) = Utils\interpretCallable($callable);
    $translation = INTERNAL_REDEFINITION_NAMESPACE . '\\' . $method;
    if ($class === null && function_exists($translation)) {
        $callable = $translation;
        # Mind the namespace-of-origin argument
        array_unshift($arguments, '');
    }
    return call_user_func_array($callable, $arguments);
}

function createStubsForInternals()
{
    $namespace = INTERNAL_REDEFINITION_NAMESPACE;
    foreach (Config\getRedefinableInternals() as $name) {
        if (function_exists($namespace . '\\' . $name)) {
            continue;
        }
        $signature = ['$__pwNamespace'];
        foreach ((new \ReflectionFunction($name))->getParameters() as $offset => $argument) {
            $formal = '';
            if ($argument->isPassedByReference()) {
                $formal .= '&';
            }
            $formal .= '$' . $argument->getName();
            $isVariadic = is_callable([$argument, 'isVariadic']) ? $argument->isVariadic() : false;
            if ($argument->isOptional() || $isVariadic || ($name === 'define' && $offset === 2)) {
                continue;
            }
            $signature[] = $formal;
        }
        $refs = sprintf('[%s]', join(', ', $signature));
        $interceptor = sprintf(
            str_replace(
                '$__pwRefOffset = 0;',
                '$__pwRefOffset = 1;',
                \Patchwork\CodeManipulation\Actions\CallRerouting\CALL_INTERCEPTION_CODE
            ), 
            $refs
        );
        eval(strtr(INTERNAL_STUB_CODE, [
            '@name' => $name,
            '@signature' => join(', ', $signature),
            '@interceptor' => $interceptor,
            '@ns_for_redefinitions' => INTERNAL_REDEFINITION_NAMESPACE,
        ]));
    }
}

/**
 * This is needed, for instance, to intercept the time() call in call_user_func('time').
 *
 * For that to happen, we require that if at least one internal function is redefinable, then
 * call_user_func, preg_replace_callback and other callback-taking internal functions also be
 * redefinable: see Patchwork\Config.
 *
 * Here, we go through the callback-taking internals and add argument-inspecting patches
 * (redefinitions) to them.
 *
 * The patches are then expected to find the "nested" internal calls, such as the 'time' argument
 * in call_user_func('time'), and invoke their respective redefinitions, if any.
 */
function connectDefaultInternals()
{
    # call_user_func() etc. are not a problem if no other internal functions are redefined
    if (Config\getRedefinableInternals() === []) {
        return;
    }
    foreach (Config\getDefaultRedefinableInternals() as $function) {
        # Which arguments are callbacks? Store their offsets in the following array.
        $offsets = [];
        foreach ((new \ReflectionFunction($function))->getParameters() as $offset => $argument) {
            $name = $argument->getName();
            if (strpos($name, 'call') !== false || strpos($name, 'func') !== false) {
                $offsets[] = $offset;
            }
        }
        connect($function, function() use ($function, $offsets) {
            # This is the argument-inspecting patch.
            $args = Stack\top('args');
            $caller = Stack\all()[1];
            foreach ($offsets as $offset) {
                # Callback absent
                if (!isset($args[$offset])) {
                    continue;
                }
                $callable = $args[$offset];
                # Callback is a closure => definitely not internal
                if ($callable instanceof \Closure) {
                    continue;
                }
                list($class, $method, $instance) = Utils\interpretCallable($callable);
                if (empty($class)) {
                    # Callback is global function, which might be internal too.
                    $args[$offset] = function() use ($callable) {
                        return dispatchDynamic($callable, func_get_args());
                    };
                }
                # Callback involves a class => not internal either, since the only internals that
                # Patchwork can handle as of 2.0 are global functions.
                # However, we must handle all kinds of opaque access here too, such as self:: and
                # private methods, because we're actually patching a stub (see INTERNAL_STUB_CODE)
                # and not directly call_user_func itself (or usort, or any other of those).
                # We must compensate for scope that is lost, and that callback-taking functions
                # can make use of.
                if (!empty($class)) {
                    if ($class === 'self' || $class === 'static' || $class === 'parent') {
                        # We do not discriminate between early and late static binding here: FIXME.
                        $actualClass = $caller['class'];
                        if ($class === 'parent') {
                            $actualClass = get_parent_class($actualClass);
                        }
                        $class = $actualClass;
                    }

                    # When calling a parent constructor, the reference to the object being
                    # constructed needs to be extracted from the stack info.
                    # Also turned out to be necessary to solve this, without any parent
                    # constructors involved: https://github.com/antecedent/patchwork/issues/99
                    if (is_null($instance) && isset($caller['object'])) {
                        $instance = $caller['object'];
                    }
                    try {
                        $reflection = new \ReflectionMethod($class, $method);
                        $reflection->setAccessible(true);
                        $args[$offset] = function() use ($reflection, $instance) {
                            return $reflection->invokeArgs($instance, func_get_args());
                        };
                    } catch (\ReflectionException $e) {
                        # If it's an invalid callable, then just prevent the unexpected propagation
                        # of ReflectionExceptions.
                    }
                }
            }
            # Give the inspected arguments back to the *original* definition of the
            # callback-taking function, e.g. \array_map(). This works given that the
            # present patch is the innermost.
            return call_user_func_array($function, $args);
        });
    }
}

/**
 * @since 2.0.5
 *
 * As of version 2.0.5, this is used to accommodate language constructs
 * (echo, eval, exit and others) within the concept of callable.
 */
function translateIfLanguageConstruct($callable)
{
    if (!is_string($callable)) {
        return $callable;
    }
    if (in_array($callable, Config\getRedefinableLanguageConstructs())) {
        return RedefinitionOfLanguageConstructs\LANGUAGE_CONSTRUCT_PREFIX . $callable;
    } elseif (in_array($callable, Config\getSupportedLanguageConstructs())) {
        throw new Exceptions\NotUserDefined($callable);
    } else {
        return $callable;
    }
}

function resolveClassToInstantiate($class, $calledClass)
{
    $pieces = explode('\\', $class);
    $last = array_pop($pieces);
    if (in_array($last, ['self', 'static', 'parent'])) {
        $frame = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2];
        if ($last == 'self') {
            $class = $frame['class'];
        } elseif ($last == 'parent') {
            $class = get_parent_class($frame['class']);
        } elseif ($last == 'static') {
            $class = $calledClass;
        }
    }
    return ltrim($class, '\\');
}

function getInstantiator($class, $calledClass)
{
    $namespace = INSTANTIATOR_NAMESPACE;
    $class = resolveClassToInstantiate($class, $calledClass);
    $adaptedName = strtr($class, ['\\' => '__']);
    if (!class_exists("$namespace\\$adaptedName")) {
        $constructor = (new \ReflectionClass($class))->getConstructor();
        list($parameters, $arguments) = Utils\getParameterAndArgumentLists($constructor);
        $code = strtr(INSTANTIATOR_CODE, [
            '@namespace'    => INSTANTIATOR_NAMESPACE,
            '@instantiator' => $adaptedName,
            '@class'        => $class,
            '@parameters'   => $parameters,
        ]);
        RedefinitionOfNew\suspendFor(function() use ($code) {
            eval(CodeManipulation\transformForEval($code));
        });
    }
    $instantiator = "$namespace\\$adaptedName";
    return new $instantiator;
}

class State
{
    static $routes = [];
    static $queue = [];
    static $preprocessedFiles = [];
    static $routeStack = [];
}
