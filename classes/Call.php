<?php

namespace Patchwork;

class Call
{
    public $function, $line, $file, $class, $object, $type, $args;
    
    private $remainder;
    
    function __construct(array $elements, array $remainder = array())
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
        if (empty($trace)) {
            throw new Exceptions\EmptyBacktrace;
        }
        return new self(array_shift($trace), $trace);
    }
}