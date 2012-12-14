<?php

namespace Foo
{
    function identify()
    {
        return "Foo";
    }

    class FooClass
    {
        static function identify()
        {
            return "Foo";
        }
    }
}

namespace Bar
{
    function identify()
    {
        return "Bar";
    }

    class BarClass
    {
        static function identify()
        {
            return "Bar";
        }
    }
}
