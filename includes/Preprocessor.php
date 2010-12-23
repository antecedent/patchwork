<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Preprocessor;

require __DIR__ . "/Preprocessor/Source.php";
require __DIR__ . "/Preprocessor/Stream.php";
require __DIR__ . "/Preprocessor/Drivers/Generic.php";
require __DIR__ . "/Preprocessor/Drivers/Interceptor.php";

use Patchwork\Exceptions;
use Patchwork\Utils;

const DRIVERS = 'Patchwork\Preprocessor\DRIVERS';
const BLACKLIST = 'Patchwork\Preprocessor\BLACKLIST';

const OUTPUT_DESTINATION = 'php://memory';
const OUTPUT_ACCESS_MODE = 'rb+';

function preprocess(Source $s)
{
    foreach ($GLOBALS[DRIVERS] as $driver) {
        $driver($s);
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
    foreach ($GLOBALS[BLACKLIST] as $pattern) {
        if (strpos(Utils\normalizePath($file), Utils\normalizePath($path)) === 0) {
            return false;
        }
    }
    return true;
}

$GLOBALS[DRIVERS] = $GLOBALS[BLACKLIST] = array();
