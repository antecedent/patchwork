<?php

namespace Patchwork;

class Reference
{
    private $reference;

    function __construct(&$reference)
    {
        $this->reference = &$reference;
    }

    function &get()
    {
        return $this->reference;
    }
}