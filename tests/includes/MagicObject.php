<?php

class MagicObject
{
    function __call($method, array $args)
    {
        throw new Patchwork\Exceptions\NotImplemented(__CLASS__ . "::" . $method);
    }
}