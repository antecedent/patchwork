<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2017 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\RedefinitionOfNew;

use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\CodeManipulation\Actions\Namespaces;

const STATIC_INSTANTIATION_REPLACEMENT = '\Patchwork\CallRerouting\dispatchInstantiation(\'%s\', \Patchwork\Utils\args(%s))';
const DYNAMIC_INSTANTIATION_REPLACEMENT = '\Patchwork\CallRerouting\dispatchInstantiation(%s, \Patchwork\Utils\args(%s))';

const spliceAllInstantiations = 'Patchwork\CodeManipulation\Actions\RedefinitionOfNew\spliceAllInstantiations';

/**
 * @since 2.1.0
 */
function spliceAllInstantiations(Source $s)
{
    foreach ($s->all(T_NEW) as $new) {
        $begin = $s->skip(Source::junk(), $new);
        $end = scanInnerTokens($s, $begin, $dynamic);
        $afterEnd = $s->skip(Source::junk(), $end);
        list($argsOpen, $argsClose) = [null, null];
        if ($s->is(Generic\LEFT_ROUND, $afterEnd)) {
            list($argsOpen, $argsClose) = [$afterEnd, $s->match($afterEnd)];
        }
        spliceInstantiation($s, $new, $begin, $end, $argsOpen, $argsClose, $dynamic);
        if (hasExtraParentheses($s, $new)) {
            removeExtraParentheses($s, $new);
        }
    }
}

function spliceInstantiation(Source $s, $new, $begin, $end, $argsOpen, $argsClose, $dynamic)
{
    $class = $s->read($begin, $end - $begin + 1);
    $args = '';
    $length = $end - $new + 1;
    if ($argsOpen !== null) {
        $args = $s->read($argsOpen + 1, $argsClose - $argsOpen - 1);
        $length = $argsClose - $new + 1;
    }
    $replacement = DYNAMIC_INSTANTIATION_REPLACEMENT;
    if (!$dynamic) {
        $class = Namespaces\resolveName($s, $begin);
        $replacement = STATIC_INSTANTIATION_REPLACEMENT;
    }
    $s->splice(sprintf($replacement, $class, $args), $new, $length);
}

function getInnerTokens()
{
    return [
        '$',
        T_OBJECT_OPERATOR,
        T_DOUBLE_COLON,
        T_NS_SEPARATOR,
        T_STRING,
        T_LNUMBER,
        T_DNUMBER,
        T_WHITESPACE,
        T_CONSTANT_ENCAPSED_STRING,
        T_COMMENT,
        T_DOC_COMMENT,
        T_VARIABLE,
        T_ENCAPSED_AND_WHITESPACE,
    ];
}

function getBracketTokens()
{
    return [
        Generic\LEFT_SQUARE,
        Generic\LEFT_CURLY,
        T_CURLY_OPEN,
        T_DOLLAR_OPEN_CURLY_BRACES,
    ];
}

function getDynamicTokens()
{
    return [
        '$',
        T_OBJECT_OPERATOR,
        T_DOUBLE_COLON,
        T_LNUMBER,
        T_DNUMBER,
        T_CONSTANT_ENCAPSED_STRING,
        T_VARIABLE,
        T_ENCAPSED_AND_WHITESPACE,
    ];
}

function scanInnerTokens(Source $s, $begin, &$dynamic = null)
{
    $dynamic = false;
    $pos = $begin;
    while ($s->is(getInnerTokens(), $pos) || $s->is(getBracketTokens(), $pos)) {
        if ($s->is(getBracketTokens(), $pos)) {
            $dynamic = true;
            $pos = $s->match($pos) + 1;
        } else {
            if ($s->is(getDynamicTokens(), $pos)) {
                $dynamic = true;
            }
            $pos++;
        }
    }
    return $pos - 1;
}

function hasExtraParentheses(Source $s, $new)
{
    $left = $s->skipBack(Source::junk(), $new);
    if (!$s->is(Generic\LEFT_ROUND, $left)) {
        return false;
    }
    $beforeLeft = $s->skipBack(Source::junk(), $left);
    return !$s->is([T_STRING, T_VARIABLE], $beforeLeft);
}

function removeExtraParentheses(Source $s, $new)
{
    $left = $s->skipBack(Source::junk(), $new);
    $s->splice('', $left, 1);
    $s->splice('', $s->match($left), 1);
}
