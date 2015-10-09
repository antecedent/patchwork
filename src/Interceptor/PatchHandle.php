<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

class PatchHandle
{
    private $references = array();
    private $expirationHandlers = array();
    private $silenced = false;

    public function __destruct()
    {
        $this->removePatches();
    }

    public function addReference(&$reference)
    {
        $this->references[] = &$reference;
    }

    public function removePatches()
    {
        foreach ($this->references as &$reference) {
            $reference = null;
        }
        if (!$this->silenced) {
            foreach ($this->expirationHandlers as $expirationHandler) {
                $expirationHandler();
            }
        }
        $this->expirationHandlers = array();
    }

    public function addExpirationHandler($expirationHandler)
    {
        $this->expirationHandlers[] = $expirationHandler;
    }

    public function silence()
    {
        $this->silenced = true;
    }
}
