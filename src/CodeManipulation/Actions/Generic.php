<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\Generic;

use Patchwork\CodeManipulation\Actions\Arguments;
use Patchwork\CodeManipulation\Source;
use Patchwork\Utils;

const LEFT_ROUND = '(';
const RIGHT_ROUND = ')';
const LEFT_CURLY = '{';
const RIGHT_CURLY = '}';
const LEFT_SQUARE = '[';
const RIGHT_SQUARE = ']';
const SEMICOLON = ';';

foreach (['NAME_FULLY_QUALIFIED', 'NAME_QUALIFIED', 'NAME_RELATIVE', 'ELLIPSIS', 'ATTRIBUTE'] as $constant) {
    if (defined('T_' . $constant)) {
        define(__NAMESPACE__ . '\\' . $constant, constant('T_' . $constant));
    } else {
        define(__NAMESPACE__ . '\\' . $constant, -1);
    }
}

function markPreprocessedFiles(&$target)
{
    return function($file) use (&$target) {
        $target[$file] = true;
    };
}

function prependCodeToFunctions($code, $typedVariants = array(), $fillArgRefs = false)
{
    if (!is_array($typedVariants)) {
        $typedVariants = array(
            'void' => $typedVariants,
        );
    }
    return function(Source $s) use ($code, $typedVariants, $fillArgRefs) {
        foreach ($s->all(T_FUNCTION) as $function) {
            # Skip "use function"
            $previous = $s->skipBack(Source::junk(), $function);
            if ($s->is(T_USE, $previous)) {
                continue;
            }
            $returnType = getDeclaredReturnType($s, $function);
            $argRefs = null;
            if ($fillArgRefs) {
                $parenthesis = $s->next(LEFT_ROUND, $function);
                $args = Arguments\readNames($s, $parenthesis);
                $argRefs = Arguments\constructReferenceArray($args);
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
                $variant = $returnType && isset($typedVariants[$returnType]) ? $typedVariants[$returnType] : $code;
                if ($fillArgRefs) {
                    $variant = sprintf($variant, $argRefs);
                }
                $s->splice($variant, $bracket + 1);
            }
        }
    };
}

function getDeclaredReturnType(Source $s, $function)
{
    $parenthesis = $s->next(LEFT_ROUND, $function);
    $next = $s->skip(Source::junk(), $s->match($parenthesis));
    if ($s->is(T_USE, $next)) {
        $next = $s->skip(Source::junk(), $s->match($s->next(LEFT_ROUND, $next)));
    }
    if ($s->is(':', $next)) {
        return $s->read($s->skip(Source::junk(), $next), 1);
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
            # Skip initial declare() statements
            while ($s->read($s->skip(Source::junk(), $pos)) === 'declare') {
                $pos = $s->next(SEMICOLON, $pos);
            }
            # Enter first namespace
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
            if ($s->is([T_DOUBLE_COLON, T_NEW], $s->skipBack(Source::junk(), $match))) {
                # Not a proper class definition: either ::class syntax or anonymous class
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

function injectCodeAtEnd($code)
{
    return function(Source $s) use ($code) {
        $openTags = $s->all(T_OPEN_TAG);
        $lastOpenTag = end($openTags);
        $closeTag = $s->next(T_CLOSE_TAG, $lastOpenTag);
        $namespaceKeyword = $s->next(T_NAMESPACE, 0);
        $extraSemicolon = ';';
        if ($namespaceKeyword !== INF) {
            $semicolon = $s->next(SEMICOLON, $namespaceKeyword);
            $leftBracket = $s->next(LEFT_CURLY, $namespaceKeyword);
            if ($leftBracket < $semicolon) {
                $code = "namespace { $code }";
                $extraSemicolon = '';
            }
        }
        if ($closeTag !== INF) {
            $s->splice("<?php $code", count($s->tokens) - 1, 0, Source::APPEND);
        } else {
            $s->splice($extraSemicolon . $code, count($s->tokens) - 1, 0, Source::APPEND); 
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
