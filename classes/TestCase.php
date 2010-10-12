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
    private $filterHandles = array();

    protected $backupGlobalsBlacklist = array(
        Filtering\FILTERS,
        Preprocessing\PREPROCESSORS,
    );
  
    function filter($subject, $filter)
    {
        $this->filterHandles[] = filter($subject, $filter);
    }

    function tearDown()
    {
        foreach ($this->filterHandles as $handle) {
            dismiss($handle);
        }
    }
}
