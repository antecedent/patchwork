# Patchwork

### Version 1.2.9

A pure PHP library that lets you redefine user-defined functions at runtime. Released under the terms of the [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Requirements

Patchwork requires PHP **5.3.0** or higher to run. It should also be noted that **opcode caches** might cause Patchwork to behave incorrectly due to possible interference with its [preprocessing mechanism](http://antecedent.github.io/patchwork/docs/implementation.html#preprocessing-code).

## Functionality

Patchwork loosely replicates the functionality of [runkit_function_redefine](http://php.net/manual/en/function.runkit-function-redefine.php) in plain PHP code, with no dependencies on non-standard PHP extensions.

However, that also makes this library incapable of redefining internal PHP functions, which is possible with [Runkit](http://php.net/manual/en/book.runkit.php) or [ext/test_helpers](https://github.com/sebastianbergmann/php-test-helpers).

## Example

All these steps occur at the same runtime:

(Note, however, that this example actually requires at least two separate files to run, one of them being a dummy entry script that just includes `Patchwork.php` and the other file, which, in turn, actually contains the example. Please see [Setup](http://antecedent.github.io/patchwork/docs/setup.html) for details.)

### 1. Define a function

```php
function size($x)
{
    return count($x);
}

size(array(1, 2)); # => 2
```

### 2. Replace its definition

```php   
Patchwork\replace("size", function($x)
{
    return "huge";
});

size(array(1, 2)); # => "huge"
```

### 3. Undo the redefinition
 
```php       
Patchwork\undoAll();

size(array(1, 2)); # => 2
```

## Further Reading

For more information, please refer to the online documentation, which can be accessed and navigated using the top menu of [Patchwork's website](http://antecedent.github.io/patchwork/).

## Issues

If you come across any bugs in Patchwork, please report them [here](https://github.com/antecedent/patchwork/issues). Thanks in advance!
