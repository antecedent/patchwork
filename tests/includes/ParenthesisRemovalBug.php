<?php

function f()
{
    return function(X $x) {
        assert($x instanceof X);
    };
}

class X
{
}

f()(new X);