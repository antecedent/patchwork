# Patchwork

Version 0.1 (highly unstable)

## Introduction

Making code testable in PHP is an unusually hard task when compared to other dynamic languages. There are hardly any possibilities for metaprogramming, for example, you can't redefine functions (although the `runkit` extension allows this) or manipulate class definitions at runtime. 

This doesn't mean that it's impossible to write testable code in PHP. However, it requires quite a lot of possibly time-consuming engineering decisions, which in some cases may even distract from more important implementation details. Also, sometimes one might simply run into a large codebase with testability issues, where refactoring is not an acceptable option.

However, PHP is still a dynamic language. It allows for things like preprocessing code at runtime (before including it, of course), which is what Patchwork does. This solution is somewhat hackish, but that does pay off when dealing with otherwise untestable code.

By preprocessing code, it finally becomes possible to replace functions and methods. However, that's not the exact solution used by Patchwork. As the name suggests, Patchwork *patches* functions, that is, prepends some code to them to allow for call interception.

## Requirements

Patchwork requires PHP 5.3. This should never be a problem as Patchwork is not intended to run on production servers which may not provide it yet. Patchwork is meant for testing, which is most commonly done on development machines.

## Basic usage

Let's say there is a function named `get_number`:

    function get_number()
    {
        return 1;
    }

Using Patchwork, one may *listen* to all calls this function receives:

    Patchwork\listen("get_number", function() {
        return 2;
    });

Now, if we called `get_number()`, we would receive `2` as the result, because the listener closure *intercepts* the call and provides its own return value.

The listener doesn't have to be a closure. All callable values are accepted.

For example, this code essentially replaces the function `foo` with the function `bar`:

    Patchwork\listen("foo", "bar");

Patchwork can also be used to intercept method calls (both instance and static):

	Patchwork\listen("Class::method", $listener);

However, polymorphism is not obeyed: for example, given that `class ChildClass extends ParentClass`, a listener to `ParentClass::foo` will not intercept calls to `ChildClass::foo`.

## Skipping and dismissing listeners

You may resume the normal flow of an intercepted call by calling `Patchwork\resume()`.

For example, this will print `"foo"`:

	function print_foo()
	{
		echo "foo";
	}
	
	Patchwork\listen("print_foo", function() {
		Patchwork\resume();
		echo "bar";
	});
	
Also, `"bar"` will not be printed as the `resume` function throws an exception internally.

To completely dismiss listeners, `Patchwork\dismiss()` should be used.

Example (see the previous definition of `print_foo`):

	print_foo(); # prints "foo"
	Patchwork\listen("print_foo", function() {
		echo "not foo";
	});
	print_foo(); # prints "not foo"
	Patchwork\dismiss("print_foo");
	print_foo(); # prints "foo" again

Assigning two listeners to a single function without dismissing the first one is **not** allowed.

## Patching the code

Patchwork must be told explicitly which code to patch. For this purpose, there are some functions in the `Patchwork` namespace named `include_patched()`, `include_patched_once()`, `require_patched()` and `require_patched_once()` which imitate the native `include[_once]` and `require[_once]` constructs, as well as an `eval_patched()` function which is a wrapper around `eval()`.

All these functions not only insert code for intercepting calls, but also **propagate to further included files and evaluated code**, meaning that `include`/`require` operations, their `_once` counterparts and `eval` calls are replaced with their automatically patching equivalents provided by Patchwork.

This means that only the topmost level of `include`/`require` calls have to be replaced with `include_patched()`/`require_patched()` manually. If the system under test uses autoloading, chances are that only the autoloader needs to be included with an `include_patched()` call.

Notes:

 * Patchwork does not keep track of the functions that are patched. This means that it will allow you to assign a listener to an unpatched function, but it will not intercept any calls.
 * **ALL** functions are patched when the file that contains them is patched, and this **does** affect performance of the patched code. This is one reason why Patchwork should not be used outside of testing context.

## Use cases

 * Stubbing segments of procedural code (like the static methods found in most Active Record implementations)
 * Overriding singleton instances
 * Turning already existent or newly created objects into partial mock objects

## Limitations

 * Internal functions may **not** be patched.
 * As already mentioned, polymorphism is **not** obeyed.
 * Patchwork is **not** a full-featured stubbing/mocking framework.

## Final notes

Please remember that Patchwork is highly unstable as it is still in an early stage of development. If you decide to try it out and discover any issues, please report them in the Issues section. The same applies to any inaccuracies in this document.

Thanks for the interest!