<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation;

class State
{
    static $actions = [];
    static $importListeners = [];
    static $cacheIndex = [];
    static $cacheIndexFile;
}
