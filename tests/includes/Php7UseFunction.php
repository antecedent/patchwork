<?php

use function Patchwork\{redefine, relay};
use function Patchwork\always;

class MyClass
{
    public function foo()
    {
    }
}

redefine('MyClass::foo', always('bar'));

assert((new MyClass)->foo() === 'bar');
