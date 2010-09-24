<?php

namespace Patchwork;

class PHPUnitTestCase extends \PHPUnit_Framework_TestCase
{
    private $filters;
    
    function setUp()
    {
        $this->filters = array();
    }
    
    function tearDown()
    {
        foreach ($this->filters as $filter) {
            dismiss($filter);
        }
    }
    
    function filter($subject, $filter)
    {
        $this->filters[] = filter($subject, $filter);
    }
    
    function expect($calls, $subject, $filter)
    {
        $this->filters[] = expect($calls, $subject, $filter);
    }
}
