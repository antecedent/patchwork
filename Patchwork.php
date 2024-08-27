<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2023 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork;

if (function_exists('Patchwork\replace')) {
    return;
}

require_once __DIR__ . '/src/Exceptions.php';
require_once __DIR__ . '/src/CallRerouting.php';
require_once __DIR__ . '/src/CodeManipulation.php';
require_once __DIR__ . '/src/Utils.php';
require_once __DIR__ . '/src/Stack.php';
require_once __DIR__ . '/src/Config.php';

function redefine($subject, callable $content)
{
    $handle = null;
    foreach (array_slice(func_get_args(), 1) as $content) {
        $handle = CallRerouting\connect($subject, $content, $handle);
    }
    $handle->silence();
    return $handle;
}

function relay(?array $args = null)
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

function assertEventuallyDefined(CallRerouting\Handle $handle)
{
    $handle->unsilence();
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
    return getClass() . '::' . getFunction();
}

function configure()
{
    Config\locate();
}

function hasMissed($callable)
{
    return Utils\callableWasMissed($callable);
}

function always($value)
{
    return function() use ($value) {
        return $value;
    };
}

Utils\alias('Patchwork', [
    'redefine'   => ['replace', 'replaceLater'],
    'relay'      => 'callOriginal',
    'fallBack'   => 'pass',
    'restore'    => 'undo',
    'restoreAll' => 'undoAll',
]);

configure();

Utils\markMissedCallables();

CodeManipulation\Stream::discoverOtherWrapper();
CodeManipulation\Stream::wrap();

CodeManipulation\register([
    CodeManipulation\Actions\CodeManipulation\propagateThroughEval(),
    CodeManipulation\Actions\CallRerouting\injectCallInterceptionCode(),
    CodeManipulation\Actions\RedefinitionOfInternals\spliceNamedFunctionCalls(),
    CodeManipulation\Actions\RedefinitionOfInternals\spliceDynamicCalls(),
    CodeManipulation\Actions\RedefinitionOfNew\spliceAllInstantiations,
    CodeManipulation\Actions\RedefinitionOfNew\publicizeConstructors,
    CodeManipulation\Actions\ConflictPrevention\preventImportingOtherCopiesOfPatchwork(),
]);

CodeManipulation\onImport([
    CodeManipulation\Actions\CallRerouting\markPreprocessedFiles(),
]);

Utils\clearOpcodeCaches();

register_shutdown_function('Patchwork\Utils\clearOpcodeCaches');

CallRerouting\createStubsForInternals();
CallRerouting\connectDefaultInternals();

require __DIR__ . '/src/Redefinitions/LanguageConstructs.php';

CodeManipulation\register([
    CodeManipulation\Actions\RedefinitionOfLanguageConstructs\spliceAllConfiguredLanguageConstructs(),
    CodeManipulation\Actions\CallRerouting\injectQueueDeploymentCode(),
    CodeManipulation\Actions\CodeManipulation\injectStreamWrapperReinstatementCode(),
]);

if (Utils\wasRunAsConsoleApp()) {
    require __DIR__ . '/src/Console.php';
}
