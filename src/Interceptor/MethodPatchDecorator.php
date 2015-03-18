<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Interceptor;

use Patchwork;
use Patchwork\Stack;

class MethodPatchDecorator
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
            if (is_callable(array($patch, "bindTo"))) {
                if (isset($top["object"]) && $patch instanceof \Closure) {
                    $patch = $patch->bindTo($top["object"], $this->superclass);
                }
            }
            return runPatch($patch);
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
               $top["function"] === $this->method;
    }
}
