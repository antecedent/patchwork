<?php

namespace Foo
{
    class TestClass
    {
    }
}

namespace Bar
{
    use Foo\TestClass;
    use stdClass;

    \Patchwork\redefine('new Foo\TestClass', \Patchwork\always(new stdClass));

    $test = new class(new TestClass()) {
        public $object;

        public function __construct($object)
        {
            $this->object = $object;
        }
    };

    assert($test->object instanceof stdClass);
}
