<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork;

class Reference
{
    private $reference;

    function __construct(&$reference)
    {
        $this->reference = &$reference;
    }

    function &get()
    {
        return $this->reference;
    }
}
