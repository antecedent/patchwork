<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class CachePathConflict extends ConfigException
{
    function __construct($first, $second)
    {
        parent::__construct(sprintf(
            "Detected configuration files provide conflicting cache paths: %s and %s",
            $first,
            $second
        ));
    }
}
