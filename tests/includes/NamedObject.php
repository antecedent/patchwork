<?php

class NamedObject
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function __destruct()
    {
    }

    public static function createAnonymousSubclassInstance()
    {
        return new class extends NamedObject {
            public function __construct()
            {
            }

            public function __destruct()
            {
            }
        };
    }
}
