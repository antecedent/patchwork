<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

class PatchHandle
{
    private $references;

    public function addReference(&$references)
    {
        $this->references[] = &$references;
    }

    public function removePatches()
    {
        foreach ($this->references as &$reference) {
            $reference = null;
        }
    }
}
