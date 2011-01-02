<?php

function expectException($type, $callback)
{
    try {
        call_user_func($callback);
        trigger_error(E_USER_ERROR, "No exception thrown");
    } catch (Exception $e) {
        if (!$e instanceof $type) {
            trigger_error(E_USER_ERROR, "No exception of type $type thrown");
        }
    }
}
