<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2013 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

if (version_compare(PHP_VERSION, "5.3", "<")) {
    trigger_error("Patchwork requires PHP version 5.3.0 or higher", E_USER_ERROR);
}

require_once __DIR__ . "/lib/Exceptions.php";
require_once __DIR__ . "/lib/Interceptor.php";
require_once __DIR__ . "/lib/Preprocessor.php";
require_once __DIR__ . "/lib/Utils.php";
require_once __DIR__ . "/lib/Stack.php";

function replace($function, $replacement)
{
    return Interceptor\patch($function, $replacement);
}

function replaceLater($function, $replacement)
{
    return Interceptor\patch($function, $replacement, true);
}

function pass()
{
    throw new Exceptions\NoResult;
}

function undo(Interceptor\PatchHandle $handle)
{
    $handle->removePatches();
}

function undoAll()
{
    Interceptor\unpatchAll();
}

Preprocessor\Stream::wrap();

Preprocessor\attach(array(
    Preprocessor\Callbacks\Preprocessor\propagateThroughEval(),
    Preprocessor\Callbacks\Interceptor\markPreprocessedFiles(),
    Preprocessor\Callbacks\Interceptor\injectCallInterceptionCode(),
    Preprocessor\Callbacks\Interceptor\injectScheduledPatchApplicationCode(),
));
