<?php

namespace Patchwork;

class Result
{
    private $value;
    
    function __construct($value)
    {
        $this->value = $value;
    }
    
    function unbox()
    {
        return $this->value;
    }
}
