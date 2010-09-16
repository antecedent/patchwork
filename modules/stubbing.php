<?php

namespace Patchwork;

const STUBS = 'Patchwork\STUBS';

const SKIP_STUB = 'Patchwork\SKIP_STUB';
const RESUME_CALL = 'Patchwork\RESUME_CALL';

function dispatch($function, Call $call, &$result)
{
    foreach ($GLOBALS[STUBS][$function] as $stub) {
        $result = call_user_func($stub, $call);
        if ($result !== SKIP_STUB) {
            return $result !== RESUME_CALL;
        }
    }
    return false;
}

function stub($function, $stub)
{
    $GLOBALS[STUBS][$function][] = $stub;
    $handle = new StubExpirator;
    return $handle->add_stub($function, $stub);
}

function stub_method($context, $method, $stub)
{
    $reflector = new \ReflectionMethod($context, $method);
    $function = $reflector->class . '::' . $method;
    return stub($function, function(Call $call) use ($context, $stub) {
        if ($call->called_class === $context || $call->object === $context) {
            return call_user_func($stub, $call);
        }
        return SKIP_STUB;
    });
}

class Call
{
    var $function, $line, $file, $class, $object, $type, $args;
    var $called_class;
    var $remainder;
    
    function __construct(array $elements, array $remainder)
    {
        foreach ($elements as $key => $value) {
            $this->{$key} = $value;
        }
        $this->remainder = $remainder;
    }
    
    function next()
    {
        return $this->top($this->remainder);
    }
    
    static function top(array $trace)
    {
        return new self(array_shift($trace), $trace);
    }
}

class StubExpirator
{
    var $functions = array();
    var $stubs = array();
 
    function add_stub($function, $stub)
    {
        $this->functions[] = $function;
        $this->stubs[] = $stub;
        return $this;
    }
       
    function expire()
    {
        foreach ($this->functions as $offset => $function) {
            $function_stubs = &$GLOBALS[STUBS][$function];
            $key = array_search($this->stubs[$offset], $function_stubs, true);
            if ($key !== false) {
                unset($function_stubs[$key]);
            }
        }
        $this->functions = $this->stubs = array();
    }
}

$GLOBALS[STUBS] = array();
