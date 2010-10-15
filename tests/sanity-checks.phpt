--TEST--
Various sanity checks done by Patchwork

--FILE--
<?php

use Patchwork\Call;

require __DIR__ . "/../Patchwork.php";

echo "Can we call getResult() on an uncompleted Call object? ";
{
    $call = new Call(array(array("function" => "doStuff")));

    try {
        $call->getResult();
        echo "Yes.", PHP_EOL;
    } catch (Patchwork\Exceptions\CallResultUnavailable $e) {
        echo "No.", PHP_EOL;
    }
}

echo "Can we complete a call twice? ";
{
    $call->complete();
    try {
        $call->complete();
        echo "Yes.", PHP_EOL;
    } catch (Patchwork\Exceptions\CallAlreadyCompleted $e) {
        echo "No.", PHP_EOL;
    }
}

echo "Can we return non-null values from filters? ";
{
    $call = new Call(array(array("function" => "doStuff")));

    Patchwork\filter("doStuff", function() {
        return "non-null";
    });

    try {
        Patchwork\Filtering\dispatch($call);
        echo "Yes.", PHP_EOL;
    } catch (Patchwork\Exceptions\IllegalFilterResult $e) {
        echo "No.", PHP_EOL;
    }
}

?>

--EXPECT--
Can we call getResult() on an uncompleted Call object? No.
Can we complete a call twice? No.
Can we return non-null values from filters? No.
