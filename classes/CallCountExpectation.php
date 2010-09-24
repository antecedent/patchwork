<?php

namespace Patchwork;

class CallCountExpectation
{
    private static $shutting_down = false;
    
    private $filter;
    private $subject;
    private $min, $max;
    private $calls = 0;
    
    function __construct($filter, $subject, $min, $max)
    {
        $this->filter = $filter;
        $this->subject = $subject;
        $this->min = $min;
        $this->max = $max;
    }
    
    function __invoke(Call $call)
    {
        $result = call_user_func($this->filter, $call);
        if ($result) {
            $this->calls++;
            $this->validate();
        }
        return $result;
    }
    
    private function validate($end = false)
    {
        if ($this->calls > $this->max || ($end && $this->calls < $this->min)) {
            throw new Exceptions\UnmetCallCountExpectation(
                $this->subject, $this->calls, $this->min, $this->max
            );
        }
    }
    
    static function shut_down()
    {
        self::$shutting_down = true;
    }
    
    function __destruct()
    {
        try {
            $this->validate(true);
        } catch (Exceptions\UnmetCallCountExpectation $e) {
            if (self::$shutting_down) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            } else {
                throw $e;
            }
        }
    }
}

register_shutdown_function('Patchwork\CallCountExpectation::shut_down');