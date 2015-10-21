# Patchwork 1.4

    composer require antecedent/patchwork

A pure PHP library that lets you redefine user-defined functions at runtime. Released under the terms of the [MIT license](http://www.opensource.org/licenses/mit-license.php).

## Functionality and Limitations

In other words, Patchwork is a partial implementation of [`runkit_function_redefine`](http://php.net/runkit_function_redefine) in userland PHP 5.4 code.

As of now, it only works with user-defined functions and methods, including static, final, and non-public ones.

Internal function redefinition functionality is currently only offered by core PHP extensions: [Runkit](http://php.net/manual/en/book.runkit.php), [ext/test_helpers](https://github.com/sebastianbergmann/php-test-helpers), and
[krakjoe/uopz](https://github.com/krakjoe/uopz).

It is, however, planned and being developed for Patchwork's next major release.

## Requirements

Patchwork requires at least either Zend's PHP 5.4.0 or HHVM 3.6.0 to run. Compatibility with lower versions of HHVM is possible, but has not been tested.

## Setup

Upon obtaining the package, it is necessary to import it manually:

```php
require 'vendor/antecedent/patchwork/Patchwork.php';
```

However, inserting this statement in an arbitrary point in your code will likely result in an error:

    Warning: Please import Patchwork from a point in your code where no user-defined function is yet defined.

It is highly recommended to comply to this warning.

When absolutely necessary, however, one can always use the `@` operator to suppress this warning:

```php
@require 'vendor/antecedent/patchwork/Patchwork.php';
```

## Example

After running the following code, any existing and upcoming instances of [Collection](http://laravel.com/docs/5.1/collections)
will automatically have an `$axe` in them.

This is done by redefining all methods of the class. The new definition ensures that an `$axe` is present and
then relays control to the original definition.

```php
use function Patchwork\{redefine, relay};

redefine([Collection::class, '*'], function() use ($axe)
{
    if (!$this->contains($axe)) {
        $this->push($axe);
    }
    return relay();
});
```

## Wiki

The [wiki](https://github.com/antecedent/patchwork/wiki) contains detailed usage instructions and implementation notes.

## Issues

If you come across any bugs in Patchwork, please report them [here](https://github.com/antecedent/patchwork/issues). Thank you!
