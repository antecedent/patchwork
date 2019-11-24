<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class InternalsNotSupportedOnHHVM extends CallbackException
{
    protected $message = "As of version 2.1, Patchwork cannot redefine internal functions and methods (such as %s) on HHVM.";
}
