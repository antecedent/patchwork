<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork\Preprocessor;

require __DIR__ . "/Preprocessor/Source.php";
require __DIR__ . "/Preprocessor/Stream.php";
require __DIR__ . "/Preprocessor/Callbacks/Generic.php";
require __DIR__ . "/Preprocessor/Callbacks/Interceptor.php";
require __DIR__ . "/Preprocessor/Callbacks/Preprocessor.php";

use Patchwork\Exceptions;
use Patchwork\Utils;

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function preprocess(Source $s)
{
    foreach (State::$callbacks as $callback) {
        $callback($s);
    }
}

function preprocessString($code)
{
    $source = new Source(token_get_all($code));
    preprocess($source);
    return (string) $source;
}

function preprocessAndEval($code)
{
    $prefix = "<?php ";
    return eval(substr(preprocessString($prefix . $code), strlen($prefix)));
}

function preprocessAndOpen($file)
{
    $resource = fopen(OUTPUT_DESTINATION, OUTPUT_ACCESS_MODE);
    $code = file_get_contents($file, true);
    $source = new Source(token_get_all($code));
    $source->file = $file;
    preprocess($source);
    fwrite($resource, $source);
    rewind($resource);
    return $resource;
}

function shouldPreprocess($file)
{
    foreach (State::$blacklist as $path) {
        if (strpos(Utils\normalizePath($file), $path) === 0) {
            return false;
        }
    }
    return true;
}

function attach($callbacks)
{
    State::$callbacks = array_merge(State::$callbacks, (array) $callbacks);
}

function exclude($path)
{
    State::$blacklist[] = Utils\normalizePath($path);
}

class State
{
    static $callbacks = array();
    static $blacklist = array();
}
