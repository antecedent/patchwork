<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork;

require_once __DIR__ . "/internals/Exceptions.php";
require_once __DIR__ . "/internals/Patches.php";
require_once __DIR__ . "/internals/Preprocessor.php";
require_once __DIR__ . "/internals/Splices.php";
require_once __DIR__ . "/internals/Utils.php";
require_once __DIR__ . "/internals/CacheCheck.php";

function patch($function, $patch)
{
    return Patches\register($function, $patch);
}

function unpatch(array $handle)
{
    Patches\unregister($handle);
}

function resume()
{
    throw new Exceptions\CallResumed;
}

function traceCall()
{
    return Patches\traceCall();
}

function getCallProperty($property)
{
    return Patches\getCallProperty($property);
}

function getCallProperties()
{
    return Patches\getCallProperties();
}

CacheCheck\run();

Preprocessor\Stream::wrap();

spl_autoload_register(Utils\autoload(__NAMESPACE__, __DIR__ . "/classes/"));

$GLOBALS[Preprocessor\CALLBACKS] = array(
    Preprocessor\prependCodeToFunctions(Utils\condense(Splices\CALL_HANDLING_SPLICE)),
    Preprocessor\replaceTokens(T_EVAL, Splices\EVAL_REPLACEMENT_SPLICE),
);


