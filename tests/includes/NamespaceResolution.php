<?php

namespace A\B\C
{
    use A\D;

    assert(new D\E\Foo instanceof \A\D\E\Foo);
}

namespace A\D\E
{
    class Foo
    {
    }
}
