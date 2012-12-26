<?php

class FooObject
{
    function getFoo()
    {
        return "foo";
    }
}

class BarObject extends FooObject
{
    function getBar()
    {
        return "bar";
    }
}

class BazObject extends BarObject
{
    function getBaz()
    {
        return "baz";
    }

    function getBar()
    {
        return "bar (overridden)";
    }
}
