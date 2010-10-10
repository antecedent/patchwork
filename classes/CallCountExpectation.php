<?php

namespace Patchwork;

class CallCountExpectation
{
    private static $shuttingDown = false;
    
    private $min, $max;
    private $calls = 0;
    private $callsOnLastFailure;
    private $origin;
    
    function __construct($min, $max, $origin)
    {
        $this->min = $min;
        $this->max = $max;
        $this->origin = $origin;
    }
    
    function __invoke()
    {
        $this->calls++;
        $this->validate();
    }
    
    private function validate($end = false)
    {
        if ($this->calls === $this->callsOnLastFailure) {
            return;
        }
        if ($this->calls > $this->max || ($end && $this->calls < $this->min)) {
            $this->callsOnLastFailure = $this->calls;
            throw new Exceptions\UnmetCallCountExpectation(
                $this->calls, $this->min, $this->max, $this->origin
            );
        }
    }
    
    static function shutDown()
    {
        self::$shuttingDown = true;
    }
    
    function __destruct()
    {
        try {
            $this->validate(true);
        } catch (Exceptions\UnmetCallCountExpectation $e) {
            if (self::$shuttingDown) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            } else {
                throw $e;
            }
        }
    }
}

register_shutdown_function('Patchwork\CallCountExpectation::shutDown');
