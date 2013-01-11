<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
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
            $s->splice(RIGHT_PARENTHESIS, $s->findMatchingBracket($pos));
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

function injectFalseExpressionAtBeginnings($expression)
{
    return function(Source $s) use ($expression) {
        $openingTags = $s->findAll(T_OPEN_TAG);
        $openingTagsWithEcho = $s->findAll(T_OPEN_TAG_WITH_ECHO);
        if (empty($openingTags) && empty($openingTagsWithEcho)) {
            return;
        }
        if (empty($openingTagsWithEcho) || reset($openingTags) < reset($openingTagsWithEcho)) {
            $pos = reset($openingTags);
            $namespaceKeyword = $s->findNext(T_NAMESPACE, $pos);
            if ($namespaceKeyword !== INF) {
                $semicolon = $s->findNext(SEMICOLON, $namespaceKeyword);
                $leftBracket = $s->findNext(LEFT_CURLY_BRACKET, $namespaceKeyword);
                $pos = min($semicolon, $leftBracket);
            }
            $s->splice(' ' . $expression . ";", $pos + 1);
        } else {
            $openingTag = reset($openingTagsWithEcho);
            $closingTag = $s->findNext(T_CLOSE_TAG, $openingTag);
            $semicolon = $s->findNext(SEMICOLON, $openingTag);
            $s->splice(' (' . $expression . ') ?: (', $openingTag + 1);
            $s->splice(') ', min($closingTag, $semicolon));
        }
    };
}

function injectCodeAfterClassDefinitions($code)
{
    return function(Source $s) use ($code) {
        foreach ($s->findAll(T_CLASS) as $match) {
            $leftBracket = $s->findNext(LEFT_CURLY_BRACKET, $match);
            if ($leftBracket === INF) {
                continue;
            }
            $rightBracket = $s->findMatchingBracket($leftBracket);
            if ($rightBracket === INF) {
                continue;
            }
            $s->splice($code, $rightBracket + 1);
        }
    };
}
