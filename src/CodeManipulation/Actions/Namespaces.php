<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2017 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\Namespaces;

use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;

function collectNamespaceBoundaries(Source $s)
{
    if (!$s->has(T_NAMESPACE)) {
        return ['' => [[0, INF]]];
    }
    $result = [];
    foreach ($s->all(T_NAMESPACE) as $keyword) {
        if ($s->next(';', $keyword) < $s->next(Generic\LEFT_CURLY, $keyword)) {
            return [scanQualifiedName($s, $keyword + 1) => [[0, INF]]];
        }
        $begin = $s->next(Generic\LEFT_CURLY, $keyword) + 1;
        $end = $s->match($begin) - 1;
        $name = scanQualifiedName($s, $keyword + 1);
        if (!isset($result[$name])) {
            $result[$name] = [];
        }
        $result[$name][] = [$begin, $end];
    }
    return $result;
}

function collectUseDeclarations(Source $s, $begin)
{
    $result = ['class' => [], 'function' => [], 'const' => []];
    # only tokens that are siblings bracket-wise are considered,
    # so trait-use instances are not an issue
    foreach ($s->siblings(T_USE, $begin) as $keyword) {
        # skip if closure-use
        $next = $s->skip(Source::junk(), $keyword);
        if ($s->is(Generic\LEFT_ROUND, $next)) {
            continue;
        }
        parseUseDeclaration($s, $next, $result);
    }
    return $result;
}

function parseUseDeclaration(Source $s, $pos, array &$aliases, $prefix = '', $type = 'class')
{
    $lastPart = null;
    $whole = $prefix;
    while (true) {
        switch ($s->tokens[$pos][Source::TYPE_OFFSET]) {
            case T_FUNCTION:
                $type = 'function';
                break;
            case T_CONST:
                $type = 'const';
                break;
            case T_NS_SEPARATOR:
                if (!empty($whole)) {
                    $whole .= '\\';
                }
                break;
            case T_STRING:
                $lastPart = $s->tokens[$pos][Source::STRING_OFFSET];
                $whole .= $lastPart;
                break;
            case T_AS:
                $pos = $s->skip(Source::junk(), $pos);
                $aliases[$type][$s->tokens[$pos][Source::STRING_OFFSET]] = $whole;
                $lastPart = null;
                $whole = $prefix;
                break;
            case ',':
                if ($lastPart !== null) {
                    $aliases[$type][$lastPart] = $whole;
                }
                $lastPart = null;
                $whole = $prefix;
                $type = 'class';
                break;
            case Generic\LEFT_CURLY:
                parseUseDeclaration($s, $pos, $aliases, $prefix . '\\', $type);
                break;
            case T_WHITESPACE:
            case T_COMMENT:
            case T_DOC_COMMENT:
                break;
            default:
                if ($lastPart !== null) {
                    $aliases[$type][$whole] = $lastPart;
                }
                return;
        }
        $pos++;
    }
}

function scanQualifiedName(Source $s, $begin)
{
    $result = '';
    while (true) {
        switch ($s->tokens[$begin][Source::TYPE_OFFSET]) {
            case T_NS_SEPARATOR:
                if (!empty($result)) {
                    $result .= '\\';
                }
                # fall through
            case T_STRING:
                $result .= $s->tokens[$begin][Source::STRING_OFFSET];
                break;
            case T_WHITESPACE:
            case T_COMMENT:
            case T_DOC_COMMENT:
                break;
            default:
                return $result;
        }
        $begin++;
    }
}
