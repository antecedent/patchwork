<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class CachePathUnavailable extends Exception
{
    function __construct($location)
    {
        parent::__construct(sprintf(
            "The specified cache path is inexistent or read-only: %s",
            $location
        ));
    }
}
