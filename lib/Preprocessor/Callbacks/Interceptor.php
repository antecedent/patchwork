<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Callbacks\Interceptor;

use Patchwork\Preprocessor\Callbacks\Generic;
use Patchwork\Interceptor;
use Patchwork\Utils;

const CALL_INTERCEPTION_CODE = '
    $pwClosureName = __NAMESPACE__ ? __NAMESPACE__ . "\\{closure}" : "{closure}";
    $pwClass = (__CLASS__ && __FUNCTION__ !== $pwClosureName) ? __CLASS__ : null;
    $pwCalledClass = $pwClass ? \get_called_class() : null;
    if (!empty(\Patchwork\Interceptor\State::$patches[$pwClass][__FUNCTION__])) {
        $pwFrame = \count(\debug_backtrace(false));
        if (\Patchwork\Interceptor\intercept($pwClass, $pwCalledClass, __FUNCTION__, $pwFrame, $pwResult)) {
            return $pwResult;
        }
    }
    unset($pwClass, $pwCalledClass, $pwResult, $pwClosureName, $pwFrame);
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
