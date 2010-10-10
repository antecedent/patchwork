--TEST--
Scoped filters for PHPUnit

--SKIPIF--
<?php

if (file_get_contents("PHPUnit/Autoload.php", true) === false) {
    echo "skip because PHPUnit 3.5+ is not available";
}

?>

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";
require __DIR__ . "/includes/Functions.php";
require "PHPUnit/Autoload.php";

class CacheTest extends Patchwork\TestCase
{
    function testOneThing()
    {
        $this->filter('Functions\getString', Patchwork\returnValue("filtered"));
        $this->assertEquals("filtered", Functions\getString());
    }

    function testAnotherThing()
    {
        $this->filter('Functions\getString', Patchwork\returnValue("filtered again"));
        $this->assertEquals("filtered again", Functions\getString());
    }

    function testNoFiltersAreLeftOver()
    {
        $this->assertEquals("unfiltered", Functions\getString());
    }
}

$test = new PHPUnit_Framework_TestSuite("CacheTest");

$result = $test->run();

var_export($result->wasSuccessful());

?>

--EXPECT--
true