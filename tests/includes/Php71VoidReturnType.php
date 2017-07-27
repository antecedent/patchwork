<?php

class Foo
{
    public $check = false;

    public function bar() : void
    {
        $this->check = true;
    }
}

$foo = new Foo;
$foo->bar();

assert($foo->check === true);
