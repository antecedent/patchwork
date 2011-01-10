<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

require_once __DIR__ . "/lib/Exceptions.php";
require_once __DIR__ . "/lib/Interceptor.php";
require_once __DIR__ . "/lib/Preprocessor.php";
require_once __DIR__ . "/lib/Utils.php";
require_once __DIR__ . "/lib/Stack.php";
require_once __DIR__ . "/lib/CacheCheck.php";

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

function undo(array $result)
{
    Interceptor\unpatch($result);
}

function undoAll()
{
    Interceptor\unpatchAll();
}

CacheCheck\run();

Preprocessor\Stream::wrap();

Preprocessor\attach(array(
    Preprocessor\Callbacks\Preprocessor\propagateThroughEval(),
    Preprocessor\Callbacks\Interceptor\markPreprocessedFiles(),
    Preprocessor\Callbacks\Interceptor\injectCallInterceptionCode(),
));
