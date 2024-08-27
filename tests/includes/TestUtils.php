<?php

class ExceptionNotThrown extends Exception {}
class WrongExceptionThrown extends Exception {}

function expectException($type, $callback)
{
    try {
        call_user_func($callback);
        throw new ExceptionNotThrown('No exception thrown');
    } catch (ExceptionNotThrown $e) {
        // Rethrow the exception.
        throw $e;
    } catch (Exception $e) {
        if (!$e instanceof $type) {
            throw new WrongExceptionThrown("No exception of type $type thrown");
        }
    }
}
