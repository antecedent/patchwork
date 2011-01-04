<?php

function expectException($type, $callback)
{
    try {
        call_user_func($callback);
        trigger_error("No exception thrown", E_USER_ERROR);
    } catch (Exception $e) {
        if (!$e instanceof $type) {
            trigger_error("No exception of type $type thrown", E_USER_ERROR);
        }
    }
}
