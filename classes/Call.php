<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork;

class Call
{
    public $args, $function, $class, $object, $file, $line, $type;

    private $remainder;
    private $result;
    private $completed = false;
    
    function __construct($backtrace, $defaults = array())
    {
        if (empty($backtrace)) {
            throw new Exceptions\EmptyBacktrace;
        }
        foreach (array_shift($backtrace) + $defaults as $property => $value) {
            $this->{$property} = $value;
        }
        $this->remainder = $backtrace;
    }

    function getCallback()
    {
        if (isset($this->object)) {
            return array($this->object, $this->function);
        }
        if (isset($this->class)) {
            return $this->class . "::" . $this->function;
        }
        return $this->function;
    }

    /**
     * @return Patchwork\Call
     */
    function next()
    {
        return new self($this->remainder);
    }

    function complete($result = null)
    {
        if ($this->completed) {
            throw new Exceptions\MultipleCallCompletions;
        }
        $this->result = $result;
        $this->completed = true;
    }

    function isCompleted()
    {
        return $this->completed;
    }

    function &getResult()
    {
        $this->assertResultAvailable();
        $result = $this->result;
        if ($result instanceof Reference) {
            $result = &$result->get();
        }
        return $result;
    }

    function getRawResult()
    {
        $this->assertResultAvailable();
        return $this->result;
    }

    private function assertResultAvailable()
    {
        if (!$this->completed) {
            throw new Exceptions\CallResultUnavailable;
        }
    }

    /**
     * @return Patchwork\Call
     */
    static function top()
    {
        $call = new self(debug_backtrace());
        return $call->next();
    }
}
