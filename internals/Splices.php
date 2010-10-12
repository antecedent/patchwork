<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Splices;

const CALL_FILTERING_SPLICE = '
    $pwClass = __CLASS__ ? \get_called_class() : null;
    if (!empty($GLOBALS[\Patchwork\Filtering\FILTERS][$pwClass][__FUNCTION__])) {
        $pwCall = new \Patchwork\Call(\debug_backtrace(), array("class" => $pwClass));
        if (\Patchwork\Filtering\dispatch($pwCall)) {
            return $pwCall->getResult();
        }
    }
    unset($pwClass, $pwCall);
';

const EVAL_REPLACEMENT_SPLICE = '\Patchwork\Preprocessing\preprocessAndEval';
