--TEST--
Using Patchwork with PHPUnit

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

class Test extends PHPUnit_Framework_TestCase
{
    function tearDown()
    {
        Patchwork\undoAll();
    }

    function testSomething()
    {
        Patchwork\replace('getInteger', array($this, "getSomeOtherInteger"));
        $this->assertEquals(42, getInteger());
    }

    function testTheReplacementIsNoLongerInEffect()
    {
        $this->assertEquals(0, getInteger());
    }

    function getSomeOtherInteger()
    {
        return 42;
    }
}

$test = new PHPUnit_Framework_TestSuite("Test");

$result = $test->run();

assert($result->wasSuccessful());

?>
===DONE===

--EXPECT--
===DONE===
