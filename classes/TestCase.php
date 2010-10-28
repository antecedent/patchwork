<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork;

class TestCase extends \PHPUnit_Framework_TestCase
{
    private $handles = array();

    protected $backupGlobalsBlacklist = array(
        Patches\CALLBACKS,
        Patches\CALL_STACK,
        Preprocessor\CALLBACKS,
        Preprocessor\BLACKLIST,
        Preprocessor\PREPROCESSED_FILES,
    );
  
    function patch($function, $patch)
    {
        $this->handles[] = patch($function, $patch);
    }

    function tearDown()
    {
        foreach ($this->handles as $handle) {
            unpatch($handle);
        }
    }
}
