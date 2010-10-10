<?php

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
