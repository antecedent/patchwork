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

function replace($function, $patch)
{
    return Interceptor\patch($function, $patch);
}

function undo(array $handle)
{
    Interceptor\unpatch($handle);
}

function undoAll()
{
    $GLOBALS[Interceptor\PATCHES] = array();
}

function escape()
{
    throw new Exceptions\PatchEscaped;
}

CacheCheck\run();

Preprocessor\Stream::wrap();

spl_autoload_register(Utils\autoload(__NAMESPACE__, __DIR__ . "/classes/"));

$GLOBALS[Preprocessor\DRIVERS] = array(
    Preprocessor\Drivers\Preprocessor\propagateThroughEval(),
    Preprocessor\Drivers\Interceptor\markPreprocessedFiles(),
    Preprocessor\Drivers\Interceptor\injectCallHandlingCode(),
);
