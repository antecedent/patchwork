<?php

namespace Patchwork\Patches;

const CALL_FILTERING_PATCH = '
    $_pw_class = __CLASS__ ? get_called_class() : null;
    if (!empty($GLOBALS[\Patchwork\FILTERS][$_pw_class][__FUNCTION__])) {
        $_pw_call = \Patchwork\Call::top(debug_backtrace());
        $_pw_call->class = $_pw_class;
        if ($_pw_result = \Patchwork\dispatch($_pw_call)) {
            return $_pw_result->unbox();
        }
    }
';

const EVAL_REPLACEMENT_PATCH = '\Patchwork\Preprocessing\preprocess_and_eval';
