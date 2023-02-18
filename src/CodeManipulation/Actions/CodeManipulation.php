<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2023 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\CodeManipulation;

use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CodeManipulation\Source;

const EVAL_ARGUMENT_WRAPPER = '\Patchwork\CodeManipulation\transformForEval';

const STREAM_WRAPPER_REINSTATEMENT_CODE = '\Patchwork\CodeManipulation\Stream::reinstateWrapper();';

function propagateThroughEval()
{
    return Generic\wrapUnaryConstructArguments(T_EVAL, EVAL_ARGUMENT_WRAPPER);
}

function injectStreamWrapperReinstatementCode()
{
    return Generic\injectCodeAtEnd(STREAM_WRAPPER_REINSTATEMENT_CODE);
}

function flush()
{
    return function(Source $s) {
        $s->flush();
    };
}
