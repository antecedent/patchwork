<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Preprocessor\Drivers\Preprocessor;

use Patchwork\Preprocessor\Drivers\Generic;
use Patchwork\Preprocessor\Source;
use Patchwork\Interceptor;

const EVAL_REPLACEMENT_CODE = '\Patchwork\Preprocessor\preprocessAndEval';

function propagateThroughEval()
{
    return Generic\replaceTokens(T_EVAL, EVAL_REPLACEMENT_CODE);
}

function flush()
{
    return function(Source $s) {
        $s->flush();
    };
}
