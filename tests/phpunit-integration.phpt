--TEST--
Scoped patches for PHPUnit

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

class Test extends Patchwork\TestCase
{
    function testSomething()
    {
        $this->patch('getInteger', function() { return 42; });
        $this->assertEquals(42, getInteger());
    }
    
    function testThePatchIsNoLongerInEffect()
    {
        $this->assertEquals(0, getInteger());
    }
}

$test = new PHPUnit_Framework_TestSuite("Test");

$result = $test->run();

assert($result->wasSuccessful());

?>
===DONE===

--EXPECT--
===DONE===