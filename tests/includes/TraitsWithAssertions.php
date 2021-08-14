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

# Direct calls to static trait methods are silencing a PHP 8.1 deprecation
$foospeak = @FooTrait::speak();
$barspeak = @BarTrait::speak();

assert($foospeak === "spam");
assert($barspeak === "bar");
assert(Babbler::sayFoo() === "foo");
assert(Babbler::sayBar() === "bacon");
assert(Babbler::speak() === "eggs");
