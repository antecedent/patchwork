<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\RedefinitionOfLanguageConstructs;

use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;
use Patchwork\Exceptions;
use Patchwork\Config;

const LANGUAGE_CONSTRUCT_PREFIX = 'Patchwork\Redefinitions\LanguageConstructs\_';

/**
 * @since 2.0.5
 */
function spliceAllConfiguredLanguageConstructs()
{
    $mapping = getMappingOfConstructs();
    $used = [];
    $actions = [];
    foreach (Config\getRedefinableLanguageConstructs() as $construct) {
        if (isset($used[$mapping[$construct]])) {
            continue;
        }
        $used[$mapping[$construct]] = true;
        $actions[] = spliceLanguageConstruct($mapping[$construct]);
    }
    return Generic\chain($actions);
}

function getMappingOfConstructs()
{
    return [
        'echo' => T_ECHO,
        'print' => T_PRINT,
        'eval' => T_EVAL,
        'die' => T_EXIT,
        'exit' => T_EXIT,
        'isset' => T_ISSET,
        'unset' => T_UNSET,
        'empty' => T_EMPTY,
        'require' => T_REQUIRE,
        'require_once' => T_REQUIRE_ONCE,
        'include' => T_INCLUDE,
        'include_once' => T_INCLUDE_ONCE,
        'clone' => T_CLONE,
    ];
}

function getInnerTokens()
{
    return [
        '$',
        ',',
        '"',
        T_START_HEREDOC,
        T_END_HEREDOC,
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
        Generic\NAME_FULLY_QUALIFIED,
        Generic\NAME_QUALIFIED,
        Generic\NAME_RELATIVE,
    ];
}

function getBracketTokens()
{
    return [
        Generic\LEFT_ROUND,
        Generic\LEFT_SQUARE,
        Generic\LEFT_CURLY,
        T_CURLY_OPEN,
        T_DOLLAR_OPEN_CURLY_BRACES,
        Generic\ATTRIBUTE,
    ];
}

function spliceLanguageConstruct($token)
{
    return function(Source $s) use ($token) {
        foreach ($s->all($token) as $pos) {
            $s->splice('\\' . LANGUAGE_CONSTRUCT_PREFIX, $pos, 0, Source::PREPEND);
            if (lacksParentheses($s, $pos)) {
                addParentheses($s, $pos);
            }
        }
    };
}

function lacksParentheses(Source $s, $pos)
{
    if ($s->is(T_ECHO, $pos)) {
        return true;
    }
    $next = $s->skip(Source::junk(), $pos);
    return !$s->is(Generic\LEFT_ROUND, $next);
}

function addParentheses(Source $s, $pos)
{
    $pos = $s->skip(Source::junk(), $pos);
    $s->splice(Generic\LEFT_ROUND, $pos, 0, Source::PREPEND);
    while ($pos < count($s->tokens)) {
        if ($s->is(getInnerTokens(), $pos)) {
            $pos++;
        } elseif ($s->is(getBracketTokens(), $pos)) {
            $pos = $s->match($pos) + 1;
        } else {
            break;
        }
    }
    if ($s->is(Source::junk(), $pos)) {
        $pos = $s->skipBack(Source::junk(), $pos);
    }
    $s->splice(Generic\RIGHT_ROUND, $pos, 0, Source::APPEND);
}
