<?php

namespace A\B\C
{
    use A\D\E\Bar as X;

    assert(new X instanceof \A\D\E\Bar);
}

namespace A\D\E
{
    class Bar
    {
    }
}
