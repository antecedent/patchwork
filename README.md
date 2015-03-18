# Patchwork

### Version 1.3.4

A pure PHP library that lets you redefine user-defined functions at runtime. Released under the terms of the [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Functionality and Limitations

In other words, Patchwork is a partial implementation of [`runkit_function_redefine`](http://php.net/runkit_function_redefine) in userland PHP 5.3 code.

As of now, it only works with user-defined functions and methods, including static, final, and non-public ones.

Internal function redefinition functionality is currently only offered by core PHP extensions: [Runkit](http://php.net/manual/en/book.runkit.php), [ext/test_helpers](https://github.com/sebastianbergmann/php-test-helpers), and
[krakjoe/uopz](https://github.com/krakjoe/uopz).

It is, however, planned and being developed for Patchwork's next major release.

## Requirements

Patchwork requires at least either Zend's PHP 5.3.0 or HHVM 3.2.0 to run. Compatibility with lower versions of HHVM is possible, but has not been tested.

## Example

All these steps occur at the same runtime:

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

## Setup

To make the above example actually run, a dummy entry script is needed, one that would would first import Patchwork, and then the rest of the application:

```php
require 'vendor/antecedent/patchwork/Patchwork.php';
require 'actualEntryScript.php';
```

Variations on this setup are possible: see the [Setup](http://antecedent.github.io/patchwork/docs/setup.html) section of the documentation for details.

For instance, PHPUnit users will most likely want to use a `--bootstrap vendor/antecedent/patchwork/Patchwork.php` command line option.

## Installation using Composer

A sample `composer.json` importing only Patchwork would be as follows:

```json
{
    "require-dev": {
        "antecedent/patchwork": "*"
    }
}
```

## Further Reading

For more information, please refer to the online documentation, which can be accessed and navigated using the top menu of [Patchwork's website](http://antecedent.github.io/patchwork/).

## Issues

If you come across any bugs in Patchwork, please report them [here](https://github.com/antecedent/patchwork/issues). Thank you!
