<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\CodeManipulation\Actions\Preprocessor;

use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CodeManipulation\Source;

const EVAL_ARGUMENT_WRAPPER = '\Patchwork\CodeManipulation\preprocessForEval';

function propagateThroughEval()
{
    return Generic\wrapUnaryConstructArguments(T_EVAL, EVAL_ARGUMENT_WRAPPER);
}

function flush()
{
    return function(Source $s) {
        $s->flush();
    };
}
