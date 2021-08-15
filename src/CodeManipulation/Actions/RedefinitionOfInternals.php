<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\RedefinitionOfInternals;

use Patchwork\Config;
use Patchwork\CallRerouting;
use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CodeManipulation\Actions\Namespaces;

const DYNAMIC_CALL_REPLACEMENT = '\Patchwork\CallRerouting\dispatchDynamic(%s, \Patchwork\Utils\args(%s))';

function spliceNamedFunctionCalls()
{
    if (Config\getRedefinableInternals() === []) {
        return function() {};
    }
    $names = [];
    foreach (Config\getRedefinableInternals() as $name) {
        $names[strtolower($name)] = true;
    }
    return function(Source $s) use ($names) {
        foreach (Namespaces\collectNamespaceBoundaries($s) as $namespace => $boundaryList) {
            foreach ($boundaryList as $boundaries) {
                list($begin, $end) = $boundaries;
                $aliases = Namespaces\collectUseDeclarations($s, $begin)['function'];
                # Receive all aliases, leave only those for redefinable internals
                foreach ($aliases as $alias => $qualified) {
                    if (!isset($names[$qualified])) {
                        unset($aliases[$alias]);
                    } else {
                        $aliases[strtolower($alias)] = strtolower($qualified);
                    }
                }
                spliceNamedCallsWithin($s, $begin, $end, $names, $aliases);
            }
        }
    };
}

function spliceNamedCallsWithin(Source $s, $begin, $end, array $names, array $aliases)
{
    foreach ($s->within([T_STRING, Generic\NAME_FULLY_QUALIFIED, Generic\NAME_QUALIFIED, Generic\NAME_RELATIVE], $begin, $end) as $string) {
        $original = strtolower($s->read($string));
        if ($original[0] == '\\') {
            $original = substr($original, 1);
        }
        if (isset($names[$original]) || isset($aliases[$original])) {
            $previous = $s->skipBack(Source::junk(), $string);
            $hadBackslash = false;
            if ($s->is(T_NS_SEPARATOR, $previous) || $s->is(Generic\NAME_FULLY_QUALIFIED, $string)) {
                if (!isset($names[$original])) {
                    # use-aliased name cannot have a leading backslash
                    continue;
                }
                if ($s->is(T_NS_SEPARATOR, $previous)) {
                    $s->splice('', $previous, 1);
                    $previous = $s->skipBack(Source::junk(), $previous);
                }
                $hadBackslash = true;
            }
            if ($s->is([T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_STRING, T_NEW, Generic\NAME_FULLY_QUALIFIED, Generic\NAME_QUALIFIED, Generic\NAME_RELATIVE], $previous)) {
                continue;
            }
            $next = $s->skip(Source::junk(), $string);
            if (!$s->is(Generic\LEFT_ROUND, $next)) {
                continue;
            }
            if (isset($aliases[$original])) {
                $original = $aliases[$original];
            }
            $secondNext = $s->skip(Source::junk(), $next);
            $splice = '\\' . CallRerouting\INTERNAL_REDEFINITION_NAMESPACE . '\\';
            $splice .= $original . Generic\LEFT_ROUND;
            # prepend a namespace-of-origin argument to handle cases like Acme\time() vs time()
            $splice .= !$hadBackslash ? '__NAMESPACE__' : '""';
            if (!$s->is(Generic\RIGHT_ROUND, $secondNext)) {
                # right parenthesis doesn't follow immediately => there are arguments
                $splice .= ', ';
            }
            $s->splice($splice, $string, $secondNext - $string);
        }
    }
}

function spliceDynamicCalls()
{
    if (Config\getRedefinableInternals() === []) {
        return function() {};
    }
    return function(Source $s) {
        spliceDynamicCallsWithin($s, 0, count($s->tokens) - 1);
    };
}

function spliceDynamicCallsWithin(Source $s, $first, $last)
{
    $pos = $first;
    $anchor = INF;
    $suppress = false;
    while ($pos <= $last) {
        switch ($s->tokens[$pos][Source::TYPE_OFFSET]) {
            case '$':
            case T_VARIABLE:
                $anchor = min($pos, $anchor);
                break;
            case Generic\LEFT_ROUND:
                if ($anchor !== INF && !$suppress) {
                    $callable = $s->read($anchor, $pos - $anchor);
                    $arguments = $s->read($pos + 1, $s->match($pos) - $pos - 1);
                    $pos = $s->match($pos);
                    $replacement = sprintf(DYNAMIC_CALL_REPLACEMENT, $callable, $arguments);
                    $s->splice($replacement, $anchor, $pos - $anchor + 1);
                }
                break;
            case Generic\LEFT_SQUARE:
            case Generic\LEFT_CURLY:
                spliceDynamicCallsWithin($s, $pos + 1, $s->match($pos) - 1);
                $pos = $s->match($pos);
                break;
            case T_WHITESPACE:
            case T_COMMENT:
            case T_DOC_COMMENT:
                break;
            case T_OBJECT_OPERATOR:
            case T_DOUBLE_COLON:
            case T_NEW:
                $suppress = true;
                break;
            default:
                $suppress = false;
                $anchor = INF;
        }
        $pos++;
    }
}
