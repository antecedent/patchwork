<?php

namespace Functions;

function getString()
{
    return "unfiltered";
}

function writeStringToArgument(&$string)
{
    $string = "unfiltered";
    return true;
}

function evaluate($string)
{
    eval($string);
}

function &getElement($key)
{
    throw new \Patchwork\Exceptions\NotImplemented(__FUNCTION__);
}