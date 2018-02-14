<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CallRerouting;

use Patchwork;
use Patchwork\Stack;

class Decorator
{
    public $superclass;
    public $instance;
    public $method;

    private $patch;

    public function __construct($patch)
    {
        $this->patch = $patch;
    }

    public function __invoke()
    {
        $top = Stack\top();
        $superclassMatches = $this->superclassMatches();
        $instanceMatches = $this->instanceMatches($top);
        $methodMatches = $this->methodMatches($top);
        if ($superclassMatches && $instanceMatches && $methodMatches) {
            $patch = $this->patch;
            if (isset($top["object"]) && $patch instanceof \Closure) {
                $patch = $patch->bindTo($top["object"], $this->superclass);
            }
            return dispatchTo($patch);
        }
        Patchwork\fallBack();
    }

    private function superclassMatches()
    {
        return $this->superclass === null ||
               Stack\topCalledClass() === $this->superclass ||
               is_subclass_of(Stack\topCalledClass(), $this->superclass);
    }

    private function instanceMatches(array $top)
    {
        return $this->instance === null ||
               (isset($top["object"]) && $top["object"] === $this->instance);
    }

    private function methodMatches(array $top)
    {
        return $this->method === null ||
               $this->method === 'new' ||
               $top["function"] === $this->method;
    }
}
