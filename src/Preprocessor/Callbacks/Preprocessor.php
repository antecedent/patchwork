<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Callbacks\Preprocessor;

use Patchwork\Preprocessor\Callbacks\Generic;
use Patchwork\Preprocessor\Source;

const EVAL_ARGUMENT_WRAPPER = '\Patchwork\Preprocessor\preprocessForEval';

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
