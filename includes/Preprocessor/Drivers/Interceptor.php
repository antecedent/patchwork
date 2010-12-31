<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Drivers\Interceptor;

use Patchwork\Preprocessor\Drivers\Generic;
use Patchwork\Interceptor;

const CALL_HANDLING_CODE = '
    $pwClosureName = __NAMESPACE__ ? __NAMESPACE__ . "\\{closure}" : "{closure}";
    $pwClass = (__CLASS__ && __FUNCTION__ !== $pwClosureName) ? \get_called_class() : null;
    if (!empty($GLOBALS[\Patchwork\Interceptor\PATCHES][$pwClass][__FUNCTION__])) {
        $pwFrame = \count(\debug_backtrace(false));
        if (\Patchwork\Interceptor\intercept($pwClass, __FUNCTION__, $pwFrame, $pwResult)) {
            return $pwResult;
        }
    }
    unset($pwClass, $pwResult, $pwClosureName, $pwFrame);
';

function markPreprocessedFiles()
{
    return Generic\markPreprocessedFiles($GLOBALS[Interceptor\PREPROCESSED_FILES]);
}

function injectCallHandlingCode()
{
    return Generic\prependCodeToFunctions(CALL_HANDLING_CODE);
}
