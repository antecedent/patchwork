<?php

function setArrayElement(array &$array, $offset, $value)
{
    throw new Patchwork\Exceptions\NotImplemented(__METHOD__);
}

function evaluate($code)
{
    eval('' . ('') . ((string) null) . $code);
}

function getInteger()
{
    return 0;
}
