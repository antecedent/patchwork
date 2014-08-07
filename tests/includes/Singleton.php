<?php

class Singleton
{
    static function getInstance()
    {
        static $instance = null;
        if (!isset($instance)) {
            $instance = new self;
        }
        return $instance;
    }
}

class TeenageSingletonChild extends Singleton
{
	static function getInstance()
	{
		return "Y'ain't gettin' no instance from me";
	}
}
