<?php

namespace Patchwork;

const EVAL_REPLACEMENT =  '\Patchwork\patch_and_eval';
    
const CALL_INTERCEPTION_CODE = <<<'END'
    if (isset($GLOBALS[\Patchwork\STUBS][__METHOD__])) {
        $_pw_backtrace = \Patchwork\Call::top(debug_backtrace());
        if (__CLASS__) {
            $_pw_backtrace->called_class = get_called_class();
        }
        if (\Patchwork\dispatch(__METHOD__, $_pw_backtrace, $_pw_result)) {
            return $_pw_result;
        }
        unset($_pw_result, $_pw_backtrace);
    }
END;
    
function patch(Source $s)
{
    patch_functions($s);
    patch_eval_calls($s);
}

function patch_functions(Source $s)
{
    foreach ($s->find_all(T_FUNCTION) as $function) {
        $bracket   = $s->find_next(LEFT_CURLY_BRACKET, $function);
        $semicolon = $s->find_next(SEMICOLON, $function);
        if ($bracket < $semicolon) {
            $s->splice(get_call_interception_code(), $bracket + 1);
        }
    }
}

function get_call_interception_code()
{
    static $code = null;
    if ($code === null) {
        $code = preg_replace('/\s*/', '', CALL_INTERCEPTION_CODE);
    }
    return $code;
}

function patch_eval_calls(Source $s)
{
    foreach ($s->find_all(T_EVAL, $s) as $eval) {
        $s->splice(EVAL_REPLACEMENT, $eval, 1);
    }
}
