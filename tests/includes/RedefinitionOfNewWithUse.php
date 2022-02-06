<?php

namespace Foo
{
    class TestClass
    {
        public function testMethod()
        {
            return 'test';
        }
    }
}

namespace Bar
{
    use Foo\TestClass;

    \Patchwork\redefine('Foo\TestClass::testMethod', function() {
        return 'test2';
    });

    $test = new TestClass;

    assert($test->testMethod() === 'test2');
}