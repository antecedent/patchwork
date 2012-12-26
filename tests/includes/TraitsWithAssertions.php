<?php

trait FooTrait
{
    static function speak()
    {
        return "foo";
    }
}

trait BarTrait
{
    static function speak()
    {
        return "bar";
    }
}

class Babbler
{
    use FooTrait, BarTrait {
        FooTrait::speak insteadof BarTrait;
        FooTrait::speak as sayFoo;
        BarTrait::speak as sayBar;
    }

    static function speak()
    {
        return "foobar";
    }
}

assert(FooTrait::speak() === "spam");
assert(BarTrait::speak() === "bar");
assert(Babbler::sayFoo() === "foo");
assert(Babbler::sayBar() === "bacon");
assert(Babbler::speak() === "eggs");
