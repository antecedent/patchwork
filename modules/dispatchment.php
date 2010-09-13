<?php

namespace Patchwork;

const LISTENERS = 'Patchwork\LISTENERS';

function dispatch($function, &$result)
{
    if (!isset($GLOBALS[LISTENERS][$function])) {
        return false;
    }
    try {
        $result = call_user_func($GLOBALS[LISTENERS][$function]);
        return true;
    } catch (ListenerSkippedException $e) {
        return false;
    }
}

function listen($subject, $listener)
{
    if (isset($GLOBALS[LISTENERS][$subject])) {
        throw new \LogicException("A listener for $subject already exists");
    }
    $GLOBALS[LISTENERS][$subject] = $listener;
}

function dismiss($subject)
{
    unset($GLOBALS[LISTENERS][$subject]);
}

function resume()
{
    throw new ListenerSkippedException;
}

function resume_if($condition)
{
    if ($condition) {
        resume();
    }
}

function resume_unless($condition)
{
    resume_if(!$condition);
}