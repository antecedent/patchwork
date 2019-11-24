<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class ConfigKeyNotRecognized extends ConfigException
{
    function __construct($key, $list, $file)
    {
        parent::__construct(sprintf(
            "The key '%s' in the configuration file %s was not recognized. " .
            "You might have meant one of these: %s",
            $key,
            $file,
            join(', ', $list)
        ));
    }
}
