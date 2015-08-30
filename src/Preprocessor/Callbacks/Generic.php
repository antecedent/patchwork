<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor\Callbacks\Generic;

use Patchwork\Preprocessor\Source;
use Patchwork\Utils;

const LEFT_PARENTHESIS = "(";
const RIGHT_PARENTHESIS = ")";
const LEFT_CURLY_BRACKET = "{";
const SEMICOLON = ";";

function markPreprocessedFiles(&$target)
{
    return function($file) use (&$target) {
        $target[$file] = true;
    };
}

function prependCodeToFunctions($code)
{
    return function(Source $s) use ($code) {
        foreach ($s->findAll(T_FUNCTION) as $function) {
            $bracket = $s->findNext(LEFT_CURLY_BRACKET, $function);
            if (Utils\generatorsSupported()) {
                # Skip generators
                $yield = $s->findNext(T_YIELD, $bracket);
                if ($yield < $s->findMatchingBracket($bracket)) {
                    continue;
                }
            }
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
            $s->splice(RIGHT_PARENTHESIS, $s->findMatchingBracket($pos));
        }
    };
}

function injectTickingDeclaration()
{
    return function(Source $s) {
        $openTags = $s->findAll(T_OPEN_TAG);
        if (empty($openTags)) {
            return;
        }
        $s->splice(' declare(ticks=1); ', $openTags + 1);
    };
}

function chain(array $callbacks)
{
    return function(Source $s) use ($callbacks) {
        foreach ($callbacks as $callback) {
            $callback($s);
        }
    };
}
