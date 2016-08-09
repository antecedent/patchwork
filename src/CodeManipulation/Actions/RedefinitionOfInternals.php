<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\RedefinitionOfInternals;

use Patchwork\Config;
use Patchwork\CallRerouting;
use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CodeManipulation\Actions\Namespaces;

const DYNAMIC_CALL_REPLACEMENT = '\Patchwork\CallRerouting\dispatchDynamic(%s, [%s])';

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
                foreach ($aliases as $alias => $qualified) {
                    if (!isset($names[$qualified])) {
                        unset($aliases[$alias]);
                    } else {
                        $aliases[strtolower($alias)] = strtolower($qualified);
                    }
                }
                foreach ($s->within(T_STRING, $begin, $end) as $string) {
                    $original = strtolower($s->read($string));
                    if (isset($names[$original]) || isset($aliases[$original])) {
                        $previous = $s->skipBack(Source::junk(), $string);
                        if ($s->is(T_NS_SEPARATOR, $previous)) {
                             if (!isset($names[$original])) {
                                # use-aliased name cannot have a leading backslash
                                continue;
                            }
                            $s->splice('', $previous, 1);
                            $previous = $s->skipBack(Source::junk(), $previous);
                        }
                        if ($s->is([T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_STRING, T_NEW], $previous)) {
                            continue;
                        }
                        $next = $s->skip(Source::junk(), $string);
                        if (!$s->is(Generic\LEFT_ROUND, $next)) {
                            continue;
                        }
                        if (isset($aliases[$original])) {
                            $original = $aliases[$original];
                        }
                        $splice = '\\' . CallRerouting\INTERNAL_REDEFINITION_NAMESPACE . '\\' . $original;
                        $s->splice($splice, $string, 1);
                    }
                }
            }
        }
    };
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
