<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class NonNullToVoid extends Exception
{
    protected $message = 'A redefinition of a void-typed callable attempted to return a non-null result';
}
