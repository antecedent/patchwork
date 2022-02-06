<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\Namespaces;

use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;

/**
 * @since 2.1.0
 */
function resolveName(Source $s, $pos, $type = 'class')
{
    $name = scanQualifiedName($s, $pos);
    $pieces = explode('\\', $name);
    if ($pieces[0] === '') {
        return $name;
    }
    $uses = collectUseDeclarations($s, $pos);
    if (isset($uses[$type][$name])) {
        return '\\' . ltrim($uses[$type][$name], ' \\');
    }
    if (isset($uses['class'][$pieces[0]])) {
        $name = '\\' . ltrim($uses['class'][$pieces[0]] . '\\' . join('\\', array_slice($pieces, 1)), '\\');
    } else {
        $name = '\\' . ltrim(getNamespaceAt($s, $pos) . '\\' . $name, '\\');
    }
    return $name;
}

/**
 * @since 2.1.0
 */
function getNamespaceAt(Source $s, $pos)
{
    foreach (collectNamespaceBoundaries($s) as $namespace => $boundaryPairs) {
        foreach ($boundaryPairs as $boundaries) {
            list($begin, $end) = $boundaries;
            if ($begin <= $pos && $pos <= $end) {
                return $namespace;
            }
        }
    }
    return '';
}

function collectNamespaceBoundaries(Source $s)
{
    return $s->cache([], function() {
        if (!$this->has(T_NAMESPACE)) {
            return ['' => [[0, INF]]];
        }
        $result = [];
        foreach ($this->all(T_NAMESPACE) as $keyword) {
            if ($this->next(';', $keyword) < $this->next(Generic\LEFT_CURLY, $keyword)) {
                return [scanQualifiedName($this, $keyword + 1) => [[0, INF]]];
            }
            $begin = $this->next(Generic\LEFT_CURLY, $keyword) + 1;
            $end = $this->match($begin - 1) - 1;
            $name = scanQualifiedName($this, $keyword + 1);
            if (!isset($result[$name])) {
                $result[$name] = [];
            }
            $result[$name][] = [$begin, $end];
        }
        return $result;
    });
}

function collectUseDeclarations(Source $s, $begin)
{
    foreach (collectNamespaceBoundaries($s) as $boundaryPairs) {
        foreach ($boundaryPairs as $boundaries) {
            list($leftBoundary, $rightBoundary) = $boundaries;
            if ($leftBoundary <= $begin && $begin <= $rightBoundary) {
                $begin = $leftBoundary;
                break;
            }
        }
    }
    return $s->cache([$begin], function($begin) {
        $result = ['class' => [], 'function' => [], 'const' => []];
        # only tokens that are siblings bracket-wise are considered,
        # so trait-use instances are not an issue
        foreach ($this->siblings(T_USE, $begin) as $keyword) {
            # skip if closure-use
            $next = $this->skip(Source::junk(), $keyword);
            if ($this->is(Generic\LEFT_ROUND, $next)) {
                continue;
            }
            parseUseDeclaration($this, $next, $result);
        }
        return $result;
    });
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
            case Generic\NAME_FULLY_QUALIFIED:
            case Generic\NAME_QUALIFIED:
            case Generic\NAME_RELATIVE:
                $update = $s->tokens[$pos][Source::STRING_OFFSET];
                $parts = explode('\\', $update);
                $whole .= $update;
                $lastPart = end($parts);
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
                parseUseDeclaration($s, $pos + 1, $aliases, $prefix . '\\', $type);
                break;
            case T_WHITESPACE:
            case T_COMMENT:
            case T_DOC_COMMENT:
                break;
            default:
                if ($lastPart !== null) {
                    $aliases[$type][$lastPart] = $whole;
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
            case Generic\NAME_FULLY_QUALIFIED:
            case Generic\NAME_QUALIFIED:
            case Generic\NAME_RELATIVE:
            case T_STATIC:
                $result .= $s->tokens[$begin][Source::STRING_OFFSET];
                break;
            case T_WHITESPACE:
            case T_COMMENT:
            case T_DOC_COMMENT:
                break;
            default:
                return str_replace('\\\\', '\\', $result);
        }
        $begin++;
    }
}
