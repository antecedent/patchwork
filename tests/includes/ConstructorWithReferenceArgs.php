<?php

class Constructee
{
    public function __construct(&$reference)
    {
        $reference = null;
    }
}

$variable = 123;

new Constructee($variable);
