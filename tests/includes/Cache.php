<?php

class Cache
{
    /**
     * Stores a value in the cache, associating it with the provided key.
     */
    static function store($key, $value, $timeToLive = INF)
    {
        return;
    }

    /**
     * Tries to fetch the value associated with the provided key from the
     * cache. On success, returns TRUE and writes the result to the second
     * argument, and otherwise, returns FALSE and leaves that argument
     * unchanged.
     *
     * @return boolean
     */
    static function fetch($key, &$result)
    {
        throw new Patchwork\Exceptions\NotImplemented(__METHOD__);
    }
}