<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Redefinitions\LanguageConstructs;

function _echo($string)
{
    foreach (func_get_args() as $argument) {
        echo $argument;
    }
}

function _print($string)
{
    return print($string);
}

function _eval($code)
{
    return eval($code);
}

function _die($message = null)
{
    die($message);
}

function _exit($message = null)
{
    exit($message);
}

function _isset(&$lvalue)
{
    return isset($lvalue);
}

function _unset(&$lvalue)
{
    unset($lvalue);
}

function _empty(&$lvalue)
{
    return empty($lvalue);
}

function _require($path)
{
    return require($path);
}

function _require_once($path)
{
    return require_once($path);
}

function _include($path)
{
    return include($path);
}

function _include_once($path)
{
    return include_once($path);
}

function _clone($object)
{
    return clone $object;
}
