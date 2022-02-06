<?php

namespace Foo
{
    class TestClass
    {
        public static function testMethod()
        {
            return 'test';
        }
    }
}

namespace Bar
{
    use Foo\TestClass;
    use Patchwork\redefine;

    redefine('Foo\TestClass::testMethod', function() {
        return 'test2';
    });

    assert(TestClass::testMethod() === 'test2');
}