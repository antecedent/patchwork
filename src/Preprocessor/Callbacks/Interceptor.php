<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2014 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Callbacks\Interceptor;

use Patchwork\Preprocessor\Callbacks\Generic;
use Patchwork\Interceptor;
use Patchwork\Utils;

const CALL_INTERCEPTION_CODE = '
    $__pwClosureName = __NAMESPACE__ ? __NAMESPACE__ . "\\{closure}" : "{closure}";
    $__pwClass = (__CLASS__ && __FUNCTION__ !== $__pwClosureName) ? __CLASS__ : null;
    $__pwCalledClass = $__pwClass ? \get_called_class() : null;
    if (!empty(\Patchwork\Interceptor\State::$patches[$__pwClass][__FUNCTION__])) {
        $__pwFrame = \count(\debug_backtrace(false));
        if (\Patchwork\Interceptor\intercept($__pwClass, $__pwCalledClass, __FUNCTION__, $__pwFrame, $__pwResult)) {
            return $__pwResult;
        }
    }
    unset($__pwClass, $__pwCalledClass, $__pwResult, $__pwClosureName, $__pwFrame);
';

const SCHEDULED_PATCH_APPLICATION_CODE = '\Patchwork\Interceptor\applyScheduledPatches()';

function markPreprocessedFiles()
{
    return Generic\markPreprocessedFiles(Interceptor\State::$preprocessedFiles);
}

function injectCallInterceptionCode()
{
    return Generic\prependCodeToFunctions(Utils\condense(CALL_INTERCEPTION_CODE));
}

function injectScheduledPatchApplicationCode()
{
    return Generic\chain(array(
        Generic\injectFalseExpressionAtBeginnings(SCHEDULED_PATCH_APPLICATION_CODE),
        Generic\injectCodeAfterClassDefinitions(SCHEDULED_PATCH_APPLICATION_CODE . ';'),
    ));
}
