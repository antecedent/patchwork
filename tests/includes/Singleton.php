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
