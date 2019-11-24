<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork;

if (defined('Patchwork\PATCHWORK_ALREADY_RAN')) {
    return;
}

const PATCHWORK_ALREADY_RAN = true;

// Load all class and function files if Composer is not running.
if (! function_exists('Patchwork\replace')) {
    require_once __DIR__ . '/patchwork-composer/autoload.php';
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

if (Utils\runningOnHHVM()) {
    # no preprocessor needed on HHVM;
    # just let Patchwork become a wrapper for fb_intercept()
    spl_autoload_register('Patchwork\CallRerouting\deployQueue');
    return;
}

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

CodeManipulation\register([
    CodeManipulation\Actions\RedefinitionOfLanguageConstructs\spliceAllConfiguredLanguageConstructs(),
    CodeManipulation\Actions\CallRerouting\injectQueueDeploymentCode(),
]);

if (Utils\wasRunAsConsoleApp()) {
    require __DIR__ . '/src/Console/console.php';
}
