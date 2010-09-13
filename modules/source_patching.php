<?php

namespace Patchwork;

const CALL_INTERCEPTION_CODE = 'if (\Patchwork\dispatch(__METHOD__, $result)) return $result;';

function patch(Source $s)
{
    patch_functions($s);
    # also patch eval() calls
}

function patch_functions(Source $s)
{
    foreach (find_all(T_FUNCTION, $s) as $function) {
        $bracket   = find_next(LEFT_CURLY_BRACKET, $function, $s);
        $semicolon = find_next(SEMICOLON, $function, $s);
        # Make sure there is a function body
        if ($bracket < $semicolon) {
            splice(CALL_INTERCEPTION_CODE, $bracket + 1, 0, $s);
        }
    }
}
