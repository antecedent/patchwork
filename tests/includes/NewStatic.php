<?php

abstract class Foo
{
    public static function instantiate()
    {
        return new static;
    }
}

class Bar extends Foo
{
}

assert(Bar::instantiate() instanceof Bar);