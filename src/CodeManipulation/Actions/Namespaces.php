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

/**
 * @since 2.1.0
 */
function resolveName(Source $s, $pos, $type = 'class')
{
    $name = scanQualifiedName($s, $pos);
    $uses = collectUseDeclarations($s, $pos);
    if (isset($uses[$type][$name])) {
        return $uses[$type][$name];
    }
    list($prefix, $suffix) = splitQualifiedName($name);
    if (isset($uses['class'][$prefix])) {
        $prefix = $uses['class'][$prefix];
    } elseif ($prefix === null) {
        $prefix = getNamespaceAt($s, $pos);
    }
    return joinQualifiedName($prefix, $suffix);
}

/**
 * @since 2.1.0
 */
function splitQualifiedName($name)
{
    if (strpos($name, '\\') === false) {
        return [null, $name];
    }
    return explode('\\', $name, 2);
}

/**
 * @since 2.1.0
 */
function joinQualifiedName($prefix, $suffix)
{
    if (empty($suffix)) {
        return $prefix;
    }
    return '\\' . ltrim($prefix . '\\' . $suffix, '\\');
}

/**
 * @since 2.1.0
 */
function getNamespaceAt(Source $s, $pos)
{
    foreach (collectNamespaceBoundaries($s) as $namespace => $boundaryPairs) {
        foreach ($boundaryPairs as $boundaries) {
            list($begin, $end) = $boundaries;
            if ($pos >= $begin && $pos <= $end) {
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
            $end = $this->match($begin) - 1;
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
    $begin = $s->lastSibling($begin);
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
