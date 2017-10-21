<?php

namespace Tests;

use Patchwork as p;

p\redefine('new PDO', p\always(new \stdClass));

assert(new \PDO('DSN') instanceof \stdClass);

class Foo
{
    function increase($a, $b = 0)
    {
        return $a + $b;
    }
}

class Bar extends Foo
{
    function increase($a, $b = 0)
    {
        return $a * (1 + $b);
    }
}

assert((new Foo)->increase(10, 1) === 11);

p\redefine('new Tests\Foo', p\always(new Bar));

assert((new Foo)->increase(10, 1) === 20);

class First
{
    protected $factor;

    function __construct($factor = 0)
    {
        $this->factor = $factor;
    }

    function increase($number)
    {
        return $number + $this->factor;
    }
}

class Second extends First
{
    function increase($number)
    {
        return $number * (1 + $this->factor);
    }
}

assert((new First(5))->increase(5) === 10);

p\redefine('Tests\First::new', function($factor = 0) {
    return new Second($factor);
});

assert((new First(5))->increase(5) === 30);

$one = 'Tests\First';
$two = 'Tests\Second';

assert(new $one instanceof $two);
assert((new $one(2))->increase(100) === 300);
