<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Callbacks\Generic;

use Patchwork\Preprocessor\Source;

const LEFT_PARENTHESIS = "(";
const RIGHT_PARENTHESIS = ")";
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

function wrapUnaryConstructArguments($construct, $wrapper)
{
    return function(Source $s) use ($construct, $wrapper) {
        foreach ($s->findAll($construct) as $match) {
            $pos = $s->findNext(LEFT_PARENTHESIS, $match);
            $s->splice($wrapper . LEFT_PARENTHESIS, $pos + 1);
            $level = 0;
            while (isset($s->tokens[$pos])) {
                if ($s->tokens[$pos] == LEFT_PARENTHESIS) {
                    $level++;
                } elseif ($s->tokens[$pos] == RIGHT_PARENTHESIS) {
                    $level--;
                }
                if ($level == 0) {
                    $s->splice(RIGHT_PARENTHESIS, $pos);
                    break;
                }
                $pos++;
            } 
        }
    };
}
