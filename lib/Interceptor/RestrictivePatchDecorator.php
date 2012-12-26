<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

use Patchwork;
use Patchwork\Stack;

class RestrictivePatchDecorator
{
    public $superclass;
    public $instance;
    public $method;

    private $patch;

    function __construct($patch)
    {
        $this->patch = $patch;
    }
   
    function __invoke()
    {
        $top = Stack\top();
        $superclassMatches = $this->superclassMatches();
        $instanceMatches = $this->instanceMatches($top);
        $methodMatches = $this->methodMatches($top);
        if ($superclassMatches && $instanceMatches && $methodMatches) {
            return runPatch($this->patch);
        }
        Patchwork\pass();
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
               $top["function"] === $this->method;
    }
}
