<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class NotUserDefined extends CallbackException
{
    protected $message = 'Please include {"redefinable-internals": ["%s"]} in your patchwork.json.';
}
