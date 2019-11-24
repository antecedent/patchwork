<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class DefinedTooEarly extends CallbackException
{

    function __construct($callback)
    {
        $this->message = "The file that defines %s() was included earlier than Patchwork. " .
                         "This is likely a result of an improper setup; see readme for details.";
        parent::__construct($callback);
    }
}
