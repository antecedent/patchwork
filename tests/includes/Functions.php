<?php

function setArrayElement(array &$array, $offset, $value)
{
    throw new Exception(__METHOD__ . " is not implemented");
}

function evaluate($code)
{
    eval('' . ('') . ((string) null) . $code);
}

function getInteger()
{
    return 0;
}

function identity($x)
{
	return $x;
}

function getClosure()
{
	return function() {};
}

