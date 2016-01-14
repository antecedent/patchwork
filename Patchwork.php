<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

require_once __DIR__ . '/src/Exceptions.php';
require_once __DIR__ . '/src/CallRerouting.php';
require_once __DIR__ . '/src/CodeManipulation.php';
require_once __DIR__ . '/src/Utils.php';
require_once __DIR__ . '/src/Stack.php';
require_once __DIR__ . '/src/Config.php';

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

function getClass()
{
    return Stack\top('class');
}

function getCalledClass()
{
    return Stack\topCalledClass();
}

function getFunction()
{
    return Stack\top('function');
}

function getMethod()
{
    return getFunction();
}

function configure()
{
    Config\locate();
}

Utils\alias('Patchwork', [
    'redefine'   => ['replace', 'replaceLater'],
    'relay'      => 'callOriginal',
    'fallBack'   => 'pass',
    'restore'    => 'undo',
    'restoreAll' => 'undoAll',
]);

if (array_filter(Utils\getUserDefinedCallables(), 'Patchwork\Utils\isForeignName') != []) {
    trigger_error('Please import Patchwork from a point in your code ' .
        'where no user-defined function, class or trait is yet defined.', E_USER_WARNING);
}

if (Utils\runningOnHHVM()) {
    # no preprocessor needed on HHVM;
    # just let Patchwork become a wrapper for fb_intercept()
    spl_autoload_register('Patchwork\CallRerouting\deployQueue');
    return;
}

try {
    configure();
} catch (Exceptions\ConfigMissing $e) {}

CodeManipulation\Stream::wrap();

CodeManipulation\register([
    CodeManipulation\Actions\CodeManipulation\propagateThroughEval(),
    CodeManipulation\Actions\CallRerouting\injectCallInterceptionCode(),
    CodeManipulation\Actions\CallRerouting\injectQueueDeploymentCode(),
]);

CodeManipulation\onImport([
    CodeManipulation\Actions\CallRerouting\markPreprocessedFiles(),
]);

Utils\clearOpcodeCaches();
