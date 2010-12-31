# Patchwork

### Version 1.1.1

A pure PHP library that lets you redefine user-defined functions at runtime. Released under the terms of the [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Requirements

Patchwork requires PHP **5.3.0** or higher to run. Furthermore, **no opcode caches** should be enabled when using Patchwork.

## Functionality

Patchwork loosely replicates the functionality of [runkit_function_redefine](http://php.net/manual/en/function.runkit-function-redefine.php) in plain PHP code, with no dependencies on non-standard PHP extensions.

However, that also makes this library incapable of redefining internal PHP functions, which is possible with Runkit.

## Example

All these steps occur at the same runtime:

### 1. Define a function

    function size($x)
    {
        return count($x);
    }
    
    size(array(1, 2)); # => 2

### 2. Replace it with something else
    
    Patchwork\replace("size", function($x)
    {
        return "huge";
    });
    
    size(array(1, 2)); # => "huge"

### 3. Undo the replacement
        
    p\undoAll();
    
    size(array(1, 2)); # => 2

## Further Reading

For more information, please refer to the online [documentation](http://antecedent.github.com/patchwork/docs).

## Issues

If you come across any bugs in Patchwork, please report them [here](https://github.com/antecedent/patchwork/issues). Thanks in advance!
