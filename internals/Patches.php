<?php

namespace Patchwork\Patches;

const CALL_FILTERING_PATCH = '
    $pwClass = __CLASS__ ? \get_called_class() : null;
    if (!empty($GLOBALS[\Patchwork\Filtering\FILTERS][$pwClass][__FUNCTION__])) {
        $pwCall = new \Patchwork\Call(\debug_backtrace(), array("class" => $pwClass));
        if (\Patchwork\Filtering\dispatch($pwCall)) {
            return $pwCall->getResult();
        }
    }
    unset($pwClass, $pwCall);
';

const EVAL_REPLACEMENT_PATCH = '\Patchwork\Preprocessing\preprocessAndEval';
