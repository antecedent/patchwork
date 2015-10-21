<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2015 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

require_once __DIR__ . '/src/Exceptions.php';
require_once __DIR__ . '/src/CallRerouting.php';
require_once __DIR__ . '/src/CodeManipulation.php';
require_once __DIR__ . '/src/Utils.php';
require_once __DIR__ . '/src/Stack.php';

function redefine($what, callable $asWhat)
{
    return CallRerouting\connect($what, $asWhat);
}

function relay(array $args = null)
{
    return CallRerouting\relay($args);
}

function fallBack()
{
    throw new Exceptions\NoResult;
}

function restore(CallRerouting\Handle $handle)
{
    $handle->expire();
}

function restoreAll()
{
    CallRerouting\disconnectAll();
}

function silence(CallRerouting\Handle $handle)
{
    $handle->silence();
}

function enableCaching($location, $assertWritable = true)
{
    CodeManipulation\setCacheLocation($location, $assertWritable);
}

function blacklist($path)
{
    CodeManipulation\exclude($path);
}

if (array_filter(get_defined_functions()['user'], 'Patchwork\Utils\isForeignName') != []) {
    trigger_error('Please import Patchwork from a point in your code ' .
        'where no user-defined function is yet defined.', E_USER_WARNING);
}

Utils\alias('Patchwork', [
    'redefine'   => ['replace', 'replaceLater'],
    'relay'      => 'callOriginal',
    'fallBack'   => 'pass',
    'restore'    => 'undo',
    'restoreAll' => 'undoAll',
]);

if (Utils\runningOnHHVM()) {
    # no preprocessor needed on HHVM;
    # just let Patchwork become a wrapper for fb_intercept()
    spl_autoload_register('Patchwork\CallRerouting\deployQueue');
    register_shutdown_function('Patchwork\undoAll');
    return;
}

enableCaching(__DIR__ . '/cache', false);

CodeManipulation\Stream::wrap();

CodeManipulation\attach([
    CodeManipulation\Actions\Preprocessor\propagateThroughEval(),
    CodeManipulation\Actions\CodeManipulation\injectCallInterceptionCode(),
    CodeManipulation\Actions\CodeManipulation\injectQueueDeploymentCode(),
]);

CodeManipulation\onImport([
    CodeManipulation\Actions\CodeManipulation\markPreprocessedFiles(),
]);

Utils\clearOpcodeCaches();

