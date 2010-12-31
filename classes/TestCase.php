<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://antecedent.github.com/patchwork
 */
namespace Patchwork;

class TestCase extends \PHPUnit_Framework_TestCase
{
    private $handles = array();

    protected $backupGlobalsBlacklist = array(
        Stack\OFFSETS,
        Preprocessor\DRIVERS,
        Preprocessor\BLACKLIST,
        Interceptor\PATCHES,
        Interceptor\PREPROCESSED_FILES,
    );
  
    function replace($function, $patch)
    {
        $this->handles[] = replace($function, $patch);
    }

    function replaceLater($function, $patch)
    {
        $this->handles[] = replaceLater($function, $patch);
    }

    function tearDown()
    {
        foreach ($this->handles as $handle) {
            undo($handle);
        }
    }
}
