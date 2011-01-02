<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

require_once __DIR__ . "/includes/Exceptions.php";
require_once __DIR__ . "/includes/Interceptor.php";
require_once __DIR__ . "/includes/Preprocessor.php";
require_once __DIR__ . "/includes/Utils.php";
require_once __DIR__ . "/includes/Stack.php";
require_once __DIR__ . "/includes/CacheCheck.php";

function replace($function, $replacement)
{
    return Interceptor\patch($function, $replacement);
}

function replaceLater($function, $replacement)
{
    return Interceptor\patch($function, $replacement, true);
}

function shift()
{
    throw new Exceptions\NoResult;
}

function top($property = null)
{
    return Stack\top($property);
}

function topOffset()
{
    return Stack\topOffset();
}

function undo(array $handle)
{
    Interceptor\unpatch($handle);
}

function undoAll()
{
    Interceptor\unpatchAll();
}

CacheCheck\run();

Preprocessor\Stream::wrap();

spl_autoload_register(Utils\autoload(__NAMESPACE__, __DIR__ . "/classes/"));

$GLOBALS[Preprocessor\DRIVERS] = array(
    Preprocessor\Drivers\Preprocessor\propagateThroughEval(),
    Preprocessor\Drivers\Interceptor\markPreprocessedFiles(),
    Preprocessor\Drivers\Interceptor\injectCallHandlingCode(),
);
