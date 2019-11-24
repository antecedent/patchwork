<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Config;

class State
{
    static $blacklist = [];
    static $whitelist = [];
    static $cachePath;
    static $redefinableInternals = [];
    static $redefinableLanguageConstructs = [];
    static $newKeywordRedefinable = false;
    static $timestamp = 0;
}
