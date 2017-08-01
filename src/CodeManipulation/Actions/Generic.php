<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2017 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\Generic;

use Patchwork\CodeManipulation\Source;
use Patchwork\Utils;

const LEFT_ROUND = '(';
const RIGHT_ROUND = ')';
const LEFT_CURLY = '{';
const RIGHT_CURLY = '}';
const LEFT_SQUARE = '[';
const RIGHT_SQUARE = ']';
const SEMICOLON = ';';

function markPreprocessedFiles(&$target)
{
    return function($file) use (&$target) {
        $target[$file] = true;
    };
}

function prependCodeToFunctions($code, $skipVoidTyped = true)
{
    return function(Source $s) use ($code, $skipVoidTyped) {
        foreach ($s->all(T_FUNCTION) as $function) {
            # Skip "use function"
            $previous = $s->skipBack(Source::junk(), $function);
            if ($s->is(T_USE, $previous)) {
                continue;
            }
            if ($skipVoidTyped && isVoidTyped($s, $function)) {
                continue;
            }
            $bracket = $s->next(LEFT_CURLY, $function);
            if (Utils\generatorsSupported()) {
                # Skip generators
                $yield = $s->next(T_YIELD, $bracket);
                if ($yield < $s->match($bracket)) {
                    continue;
                }
            }
            $semicolon = $s->next(SEMICOLON, $function);
            if ($bracket < $semicolon) {
                $s->splice($code, $bracket + 1);
            }
        }
    };
}

function isVoidTyped(Source $s, $function)
{
    $parenthesis = $s->next(LEFT_ROUND, $function);
    $next = $s->skip(Source::junk(), $s->match($parenthesis));
    if ($s->is(T_USE, $next)) {
        $next = $s->skip(Source::junk(), $s->match($s->next(LEFT_ROUND, $next)));
    }
    if ($s->is(':', $next)) {
        return $s->read($s->skip(Source::junk(), $next), 1) === 'void';
    }
    return false;
}

function wrapUnaryConstructArguments($construct, $wrapper)
{
    return function(Source $s) use ($construct, $wrapper) {
        foreach ($s->all($construct) as $match) {
            $pos = $s->next(LEFT_ROUND, $match);
            $s->splice($wrapper . LEFT_ROUND, $pos + 1);
            $s->splice(RIGHT_ROUND, $s->match($pos));
        }
    };
}

function injectFalseExpressionAtBeginnings($expression)
{
    return function(Source $s) use ($expression) {
        $openingTags = $s->all(T_OPEN_TAG);
        $openingTagsWithEcho = $s->all(T_OPEN_TAG_WITH_ECHO);
        if (empty($openingTags) && empty($openingTagsWithEcho)) {
            return;
        }
        if (!empty($openingTags) &&
            (empty($openingTagsWithEcho) || reset($openingTags) < reset($openingTagsWithEcho))) {
            $pos = reset($openingTags);
            $namespaceKeyword = $s->next(T_NAMESPACE, $pos);
            if ($namespaceKeyword !== INF) {
                $semicolon = $s->next(SEMICOLON, $namespaceKeyword);
                $leftBracket = $s->next(LEFT_CURLY, $namespaceKeyword);
                $pos = min($semicolon, $leftBracket);
            }
            $s->splice(' ' . $expression . ';', $pos + 1);
        } else {
            $openingTag = reset($openingTagsWithEcho);
            $closingTag = $s->next(T_CLOSE_TAG, $openingTag);
            $semicolon = $s->next(SEMICOLON, $openingTag);
            $s->splice(' (' . $expression . ') ?: (', $openingTag + 1);
            $s->splice(') ', min($closingTag, $semicolon));
        }
    };
}

function injectCodeAfterClassDefinitions($code)
{
    return function(Source $s) use ($code) {
        foreach ($s->all(T_CLASS) as $match) {
            if ($s->next(T_DOUBLE_COLON, $match - 3) < $match) {
                # ::class syntax, not a class definition
                continue;
            }
            $leftBracket = $s->next(LEFT_CURLY, $match);
            if ($leftBracket === INF) {
                continue;
            }
            $rightBracket = $s->match($leftBracket);
            if ($rightBracket === INF) {
                continue;
            }
            $s->splice($code, $rightBracket + 1);
        }
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
