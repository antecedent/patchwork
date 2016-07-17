<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\CodeManipulation;

use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CodeManipulation\Source;

const EVAL_ARGUMENT_WRAPPER = '\Patchwork\CodeManipulation\transformForEval';
const STREAM_FILTER_PATH_REWRITER = '\Patchwork\CodeManipulation\StreamFilter::rewritePath';

function propagateThroughEval()
{
    return Generic\wrapUnaryConstructArguments(T_EVAL, EVAL_ARGUMENT_WRAPPER);
}

function propagateThroughStreamFilter()
{
    # TODO not choke on short open tags
    return function(Source $s) {
        foreach ($s->all([T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE]) as $import) {
            $semicolon = $s->nextOnLevel(Generic\SEMICOLON, $import);
            $s->insert(' ' . STREAM_FILTER_PATH_REWRITER . Generic\LEFT_PARENTHESIS, $import + 1);
            $s->insert(Generic\RIGHT_PARENTHESIS, $semicolon);
        }
    };
}

function flush()
{
    return function(Source $s) {
        $s->flush();
    };
}
