<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2017 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CallRerouting\InstantiationMediated;

use Patchwork\Utils;
use Patchwork\CallRerouting\Handle;
use Patchwork\Config;

function shouldHandle($callable)
{
    list($class, $method) = Utils\interpretCallable($callable);
    while ($class) {
        $class = new \ReflectionClass($class);
        if ($class->isInternal()) {
            return true;
        }
        $class = get_parent_class($class);
    }
    return false;
}

function createClassStubs()
{
    $classes = [];
    foreach (Config\getRedefinableInternals() as $callable) {
        list($class, $method) = Utils\interpretCallable($callable);
        if (!$class) {
            continue;
        }
        $reflection = new \ReflectionMethod($class, $method);
        $declaringClass = $reflection->getDeclaringClass();
        if ($declaringClass->isInternal()) {
            $classes[$declaringClass->name] = true;
        }
    }
    foreach (array_keys($classes) as $class) {
        eval(strtr('namespace Patchwork\Redefinitions; class NAME extends \PARENT { METHODS }', [
            'NAME'    => $class,
            'PARENT'  => $class,
            'METHODS' => createMethodStubs(),
        ]));
    }
}

function createMethodStubs()
{
    
}
