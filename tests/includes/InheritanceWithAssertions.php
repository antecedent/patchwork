<?php

class FooObject
{
    function getFoo()
    {
        return $this->bah();
    }

    private function bah()
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

Patchwork\Interceptor\deployQueue();

$foo = new FooObject;
$bar = new BarObject;
$baz = new BazObject;

assert($foo->getFoo() === "foo");

assert($bar->getFoo() === "foo (patched)");
assert($bar->getBar() === "bar (patched)");

assert($baz->getFoo() === "foo (patched)");
assert($baz->getBar() === "bar (overridden)");
assert($baz->getBaz() === "baz");
