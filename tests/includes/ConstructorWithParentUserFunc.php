<?php

class FooObject
{
    public $attribute;

    function __construct() {
        $this->attribute = "Initialized";
    }
}

class BarObject extends FooObject
{
    function __construct() {
        // This is actually something that happens.
        call_user_func(['parent', '__construct']);
    }
}
