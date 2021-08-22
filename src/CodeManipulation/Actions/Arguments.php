<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2021 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation\Actions\Arguments;

use Patchwork\CodeManipulation\Source;
use Patchwork\CodeManipulation\Actions\Generic;

/**
 * @since 2.1.13
 */
function readNames(Source $s, $pos)
{
    $result = [];
    $pos++;
    while (!$s->is(Generic\RIGHT_ROUND, $pos)) {
        if ($s->is([Generic\LEFT_ROUND, Generic\LEFT_SQUARE, Generic\LEFT_CURLY], $pos)) {
            $pos = $s->match($pos);
        } else {
            if ($s->is(T_VARIABLE, $pos)) {
                $result[] = $s->read($pos);
            } elseif ($s->is(Generic\ELLIPSIS, $pos)) {
                $pos = $s->skip(Source::junk(), $pos);
                $result[] = '...' . $s->read($pos);
            }
            $pos++;
        }
    }
    return $result;
}

/**
 * @since 2.1.13
 */
function constructReferenceArray(array $names)
{
    $names = array_map(function($name) {
        if ($name[0] === '.') {
            return '], ' . substr($name, 3) . ', [';
        }
        return '&' . $name;
    }, $names);
    return 'array_merge([' . join(', ', $names) . '])';
}