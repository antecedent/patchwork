<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\Exceptions;

class ConfigMalformed extends ConfigException
{
    function __construct($file, $message)
    {
        parent::__construct(sprintf(
            'The configuration file %s is malformed: %s',
            $file,
            $message
        ));
    }
}
