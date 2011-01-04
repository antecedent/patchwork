<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Callbacks\Generic;

use Patchwork\Preprocessor\Source;

const LEFT_CURLY_BRACKET = "{";
const SEMICOLON = ";";

function markPreprocessedFiles(&$target)
{
    return function(Source $s) use (&$target) {
        $target[$s->file] = true;
    };
}

function prependCodeToFunctions($code)
{
    return function(Source $s) use ($code) {
        foreach ($s->findAll(T_FUNCTION) as $function) {
            $bracket = $s->findNext(LEFT_CURLY_BRACKET, $function);
            $semicolon = $s->findNext(SEMICOLON, $function);
            if ($bracket < $semicolon) {
                $s->splice($code, $bracket + 1);
            }
        }
    };
}

function replaceTokens($search, $replacement)
{
    return function(Source $s) use ($search, $replacement) {
        foreach ($s->findAll($search) as $match) {
            $s->splice($replacement, $match, 1);
        }
    };
}
