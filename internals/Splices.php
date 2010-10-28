<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Splices;

const CALL_HANDLING_SPLICE = '
    $pwClass = __CLASS__ ? \get_called_class() : null;
    if (!empty($GLOBALS[\Patchwork\Patches\CALLBACKS][$pwClass][__FUNCTION__])) {
        if (\Patchwork\Patches\handle($pwClass, __FUNCTION__, \debug_backtrace(), $pwResult)) {
        	return $pwResult;
        }
    }
    unset($pwClass, $pwResult);
';

const EVAL_REPLACEMENT_SPLICE = '\Patchwork\Preprocessor\preprocessAndEval';
