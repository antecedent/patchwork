# Patchwork 1.5 ([.phar](https://github.com/antecedent/patchwork/releases/download/1.5.0/patchwork.phar))

Patchwork implements the redefinition ([monkey-patching](https://en.wikipedia.org/wiki/Monkey_patch)) of **user-defined** methods in PHP.

Internally, it uses a [stream wrapper](http://php.net/manual/en/class.streamwrapper.php) on `file://` to inject a simple interceptor snippet to the beginning of every method.

## Example: a DIY profiler

```php
use function Patchwork\{redefine, relay, getMethod};

$profiling = fopen('profiling.csv', 'w');

redefine('App\*', function(...$args) use ($profiling) {
    $begin = microtime(true);
    relay(); # calls the original definition
    $end = microtime(true);
    fputcsv($profiling, [getMethod(), $end - $begin]);
});
```

## Notes

* *Method redefinition* is the internally preferred metaphor for Patchwork's behavior.
* `restoreAll()` and `restore($handle)` end the lifetime of, respectively, all redefinitions, or only one of them, where `$handle = redefine(...)`.
* Closure `$this` is automatically re-bound to the enclosing class of the method being redefined.
* The behavior of `__CLASS__`, `static::class` etc. inside redefinitions disregards the metaphor. `getClass()`, `getCalledClass()`, `getMethod()` and `getFunction()` from the `Patchwork` namespace should be used instead.

## Testing-related uses

Patchwork can be used to stub static methods, which, however, is a controversial practice.

It should be applied prudently, that is, only after making oneself familiar with its pitfalls and temptations in other programming languages. For instance, in Javascript, Ruby, Python and some others, the native support for monkey-patching has made its testing-related uses more commonplace than in PHP.

Tests that use monkey-patching are no longer *unit* tests, because they become sensitive to details of implementation, not only those of interface: for example, such a test might no longer pass after switching from `time()` to `DateTime`.

That being said, they still have their place where the only economically viable alternative is having no tests at all.
