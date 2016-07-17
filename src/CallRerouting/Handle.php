<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CallRerouting;

class Handle
{
    private $references = [];
    private $expirationHandlers = [];
    private $silenced = false;
    private $tags = [];

    function __destruct()
    {
        $this->expire();
    }

    function tag($tag)
    {
        $this->tags[] = $tag;
    }

    function hasTag($tag)
    {
        return in_array($tag, $this->tags);
    }

    function addReference(&$reference)
    {
        $this->references[] = &$reference;
    }

    function expire()
    {
        foreach ($this->references as &$reference) {
            $reference = null;
        }
        if (!$this->silenced) {
            foreach ($this->expirationHandlers as $expirationHandler) {
                $expirationHandler();
            }
        }
        $this->expirationHandlers = [];
    }

    function addExpirationHandler(callable $expirationHandler)
    {
        $this->expirationHandlers[] = $expirationHandler;
    }

    function silence()
    {
        $this->silenced = true;
    }
}
