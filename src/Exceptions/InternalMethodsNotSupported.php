<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class InternalMethodsNotSupported extends CallbackException
{
    protected $message = "Methods of internal classes (such as %s) are not yet redefinable in Patchwork 2.1.";
}
