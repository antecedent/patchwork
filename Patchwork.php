<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

require_once __DIR__ . "/src/Exceptions.php";
require_once __DIR__ . "/src/Interceptor.php";
require_once __DIR__ . "/src/Preprocessor.php";
require_once __DIR__ . "/src/Utils.php";
require_once __DIR__ . "/src/Stack.php";

function replace($original, $replacement)
{
    return Interceptor\patch($original, $replacement);
}

/**
 * @deprecated
 * @alias replace
 */
function replaceLater($function, $replacement)
{
    return replace($function, $replacement);
}

function fallBack()
{
    throw new Exceptions\NoResult;
}

/**
 * @alias fallBack
 */
function pass()
{
    fallBack();
}

function callOriginal(array $args = null)
{
    return Interceptor\callOriginal($args);
}

function undo(Interceptor\PatchHandle $handle)
{
    $handle->removePatches();
}

function undoAll()
{
    Interceptor\unpatchAll();
}

function enableCaching($location, $assertWritable = true)
{
    Preprocessor\setCacheLocation($location, $assertWritable);
}

function blacklist($path)
{
    Preprocessor\exclude($path);
}

if (Utils\runningOnHHVM()) {
    # no preprocessor needed on HHVM;
    # just let Patchwork become a wrapper for fb_intercept()
    register_shutdown_function('Patchwork\undoAll');
    return;
}

enableCaching(__DIR__ . '/cache', false);

Preprocessor\Stream::wrap();

Preprocessor\attach(array(
    Preprocessor\Callbacks\Preprocessor\propagateThroughEval(),
    Preprocessor\Callbacks\Interceptor\injectCallInterceptionCode(),
    Preprocessor\Callbacks\Interceptor\injectScheduledPatchApplicationCode(),
));

Preprocessor\onImport(array(
    Preprocessor\Callbacks\Interceptor\markPreprocessedFiles(),    
));
