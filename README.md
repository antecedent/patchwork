# Patchwork

Patchwork is a library which lets you filter calls to user-defined PHP functions and methods (including private, static and final ones) and optionally short-circuit them, which is essentially equivalent to redefining the called function. Patchwork is written in pure userland PHP 5.3 code, has no dependencies on non-default extensions, and is mainly targeted at dealing with hardly testable code that needs to be tested anyway.

This library is released under [the MIT license](http://www.opensource.org/licenses/mit-license.html).

## Notice

**It is strongly advised to <u>disable any opcode caches</u> when running Patchwork.** See the "Implementation" section for an explanation.

## Introduction

Patchwork aims to ease the process of testing PHP code that was not designed to be testable. This includes heavy use of singletons, non-polymorphic static methods, final methods/classes and global functions.

The solution offered by Patchwork is call filtering:
    
    function printFoo()
    {
        print "foo";
    }
    
    Patchwork\filter("printFoo", function($call) {
        print "not exactly foo";
        $call->complete();
    });
    
    printFoo();
    
In this example, `"not exactly foo"` is printed, because the call to `printFoo` is intercepted and transferred to the filter closure which has been registered using the `Patchwork\filter` call. Also, it is the call to the `$call->complete()` method that stops the filtered function from being executed. If it were not here, the original code inside `printFoo` would still run, meaning that both strings would be printed.

There is a wide range of cases where call filtering can be employed. Using it, you can replace singleton instances with test doubles, stub static and final methods, create mock objects without extending the expected class, or simply dump some debug information on each call to a specific function.

## Implementation

Of course, things like that cannot be achieved without some sort of hacks. Patchwork is essentially based on a simple hack: it preprocesses (i.e., "patches", hence the name) any code that comes from included files or `eval` calls.

The preprocessing functionality is implemented on the top of a stream wrapper, which overrides the `file://` protocol. This way, it appends some code to all functions to intercept calls. In a function with _no_ filters attached, executing this code requires making a single call to `get_called_class` and checking for filters using the built-in `empty()` construct on a global variable (also once). Therefore, this does cause a certain performance hit, but it is generally too low to become noticeable.

Also, all usages of `eval` are replaced with calls to `Patchwork\preprocessAndEval`, which, as the name suggests, preprocesses the argument before evaluating it.

Patchwork operates on tokenized code. This is simple and reliable (no problems arise with string literals, comments, etc.) but it affects performance as well. On the other hand, this overhead is also low enough to go unnoticed in unit or functional testing environments.

Note as well that Patchwork only preprocesses code that is _included_. Reading or writing to source files does not trigger preprocessing.

And finally, all the "patches", i.e., code snippets inserted into preprocessed code, are condensed to a single line and do not contain _any_ whitespace in them, so line numbers should not be affected.

## Getting started

To start using Patchwork, you need to include `Patchwork.php` and call `Patchwork\enable()` (of course, you may alias the `Patchwork` namespace to `p` or something else):

    require "patchwork/Patchwork.php";
    Patchwork\enable();
    
The `enable` function takes an *optional* array of patterns (regular expressions) of file paths to preprocess:

    Patchwork\enable(array(
        ".*/SomeUntestableLibrary/version-\d+/.*",
    ));

These patterns are automatically wrapped in delimiters and start-to-end matchers (^$). Also, when matching paths against these patterns, all instances of `DIRECTORY_SEPARATOR` are replaced with slashes in these paths.

Upon this point, any code that is included or run through `eval`, but not will be preprocessed and available for call filtering. 

**IMPORTANT: ANY CODE THAT IS ENCOUNTERED WHEN PATCHWORK IS NOT ENABLED, INCLUDING THE SCRIPT FROM WHICH THE INITIAL CALL TO `Patchwork\enable()` IS MADE AND ANY PREVIOUSLY INCLUDED FILES, IS COMPILED AS-IS. AS A CONSEQUENCE, CALLS TO ANY OF THE FUNCTIONS APPEARING IN IT CANNOT BE FILTERED USING `Patchwork\filter()`.**

## Basic usage

Let's suppose that we have a static `Cache` class which is used by various components of the application to cache data and to retrieve it from the cache:

    # Cache.php

    class Cache
    {
        /**
         * Stores a value in the cache, associating it with the provided key 
		 * and expiring it after the optional provided time to live, which
		 * defaults to infinity.
         */
        static function store($key, $value, $timeToLive = INF) 
        {
            # ...
        }

        /**
         * Tries to fetch the value associated with the provided key from the 
		 * cache. On success, returns TRUE and writes the result to the second 
		 * argument, and otherwise, returns FALSE and leaves that argument
		 * unchanged.
         *
         * @return boolean
         */
        static function fetch($key, &$result)
        {
            # ...
        }
    }

If this class provides an actual concrete implementation (not a simplified interface to a more complex architecture), then it is clearly not an example of good software design; in fact it is more like the opposite. However, such code is encountered rather often and has to be dealt with, which is the exact purpose of Patchwork.

### Filtering calls without short-circuting them

First of all, let's simply try the call filtering functionality out by outputting a message on each cache operation:

    # Filters.php

    require __DIR__ . "/patchwork/Patchwork.php";

    Patchwork\enable();

    Patchwork\filter("Cache::store", function() {
        echo "Storing data in cache\n";
    });

    Patchwork\filter("Cache::fetch", function() {
        echo "Fetching data from cache\n";
    });

These filters will not stop the `store` and `fetch` methods from running, but the messages will appear on every call to these methods, before executing the actual code in them. Therefore, the following [PHPT](http://qa.php.net/write-test.php) test case will pass:

    --FILE--
    <?php

    require __DIR__ . "/Filters.php";
    require __DIR__ . "/Cache.php";

    Cache::store("foo", 1);
    Cache::fetch("foo", $result);
    echo $result . "\n";
    $result++;
    Cache::store("bar", $result);

    --EXPECT--
    Storing data in cache
    1
    Fetching data from cache
    Storing data to cache

Note that although we have always used lambda functions as filters, any valid PHP callback works as well.

### Accessing the arguments from a filter

Now, for something more complicated, let's display the argument values in these messages. These values are provided by the `Patchwork\Call` object which every filter receives. (Note: these objects are explained in more detail in the next chapter.)

    use Patchwork\Call;

    Patchwork\filter("Cache::store", function(Call $call) {
        list($key, $value) = $call->getArguments();
        echo "Storing '$value' as '$key'\n";
    });

    Patchwork\filter("Cache::fetch", function(Call $call) {
        echo "Fetching '$call->getArgument(0)'";
    });

Now, if we called `Cache::store("foo", 1)` and `Cache::fetch("foo", $result)` one after another, the following would appear:

    Storing '1' as 'foo'
    Fetching 'foo'

### Using filters to short-circuit calls

And finally, let's proceed to a much more useful example: completely stubbing out these two methods. This requires us to use the `complete()` method of the `Call` class:

    # Stubs.php

    require __DIR__ . "/patchwork/Patchwork.php";

    Patchwork\enable();
	
	Patchwork\filter("Cache::store", function($call) {
		$call->complete();
		list($key, $value) = $call->getArguments();
		echo "Imitating Cache::store($key, $value)\n";
	});
	
	Patchwork\filter("Cache::fetch", function($call) {
		$call->complete(false);
		echo "Imitating Cache::fetch($call->getArgument(0))\n";
	});
	
A call to `$call->complete()` schedules a `return` from the function that is being filtered, **without** executing any code inside that function. However, it does not terminate the filter itself, so in this example, the "Imitating..." mesages are still displayed.

Here is a test case to illustrate the example:

    --FILE--
    <?php

    require __DIR__ . "/Stubs.php";
    require __DIR__ . "/Cache.php";

    Cache::store("foo", 2);
    var_export(Cache::fetch("foo", $result));

    --EXPECT--
	Imitating Cache::store(foo, 2)
	Imitating Cache::fetch(foo)
	false
	
The call to `Cache::store(foo, 2)` would normally make the following `Cache::fetch` call write 2 to the `$result` variable and return TRUE, but this does not happen, as we have stubbed both methods and made the second one (`fetch`) return FALSE unconditionally.

## Patchwork\Call objects

This section provides a reference for the public API of `Patchwork\Call` objects, which represent call stack frames.

### Accessor methods

Internally, the `Call` class uses the output of `debug_backtrace` to retrieve the data about the stack frame. Because of that, you may access any of the stack frame properties which are populated by `debug_backtrace`. These properties are exposed through the following accessor methods: `getArguments()`, `getArgument($n)`, `getClass()`, `getObject()`, `getFunction()`, `getFile()`, `getLine()` and `getType()`. Note that all of these methods return NULL without throwing anything when the requested property is not populated.

In addition, there some more accessors:

 - `getCalledClass()`, which, if possible, returns the class on which the method was called (unlike `getClass()`, which returns the one in which the method is declared).
 - `getCallback()` returns a valid PHP callback corresponding to the called function. Note: this method **does not work on lambdas**.
 - `getReturnValue()` returns the value to be returned by the call (see the next subsection) or throws an exception if the call is not completed.
 - `isCompleted()` tells if the call has been completed, and therefore has a return value (see the next subsection).

### complete()

Calling this method means requesting to pop the frame off the call stack (i.e., terminate the call), returning the value that this method optionally receives as an argument (the default is NULL). However, that from an internal standpoint, all this method does is change the state of the `Call` object to "completed". The responsibility to issue an actual `return` statement has to be taken by client code, which, in call filtering context, is the patch code that Patchwork injects to each preprocessed function automatically. This means that, as already mentioned, calling the `complete()` method from a filter still permits any remaining filters to run.

Note, however, that once the `Call` object changes into a "completed" state, calling this method again always throws an exception. The exact type of these exceptions is `Patchwork\Exceptions\CallAlreadyCompleted`.

As mentioned above, this method accepts a return value as an argument, e.g. `$call->complete(true)`. To return something by *reference*, you have to wrap it in a `Patchwork\Reference` object:

    $value = 42;
    if ($function->returnsReference()) {
        $call->complete(new Patchwork\Reference($value));
    } else {
        $call->complete($value);
    }

`Call::complete()` may also be used to write values to reference arguments:

    # Requests to return NULL and writes "foo" to the second argument (mind zero-based indexing!)
    $call->complete(null, array(1 => "foo"));

Writing to arguments can be illustrated by continuing the previous example with `Cache::fetch()`. Previously, we have only stubbed `Cache::fetch` to fail indiscriminately. Now, we can make it more useful by making it write an actual result:

    Patchwork\filter("Cache::fetch", function($call) {
        if ($call->getArgument(0) == "expected key") {
            $call->complete(true, array(1 => "expected value"));
        } else {
            $call->complete(false);
        }
    });

By the way, the code snipped above may be shortened by a single line this way:

    Patchwork\filter("Cache::fetch", function($call) {
        if ($call->getArgument(0) == "expected key") {
            return $call->complete(true, array(1 => "expected value"));
        }
        $call->complete(false);
    });

This is because the return values of filters bear no meaning whatsoever.

### Call::dispatch()

! Still to be written. Do not forget to include a call forwarding example.

### Call::create()

This is a **static** method. It should be used instead of the privatized constructor to create new instances of `Patchwork\Call`:

    $calls[] = Patchwork\Call::create(debug_backtrace());
    $calls[] = Patchwork\Call::create(debug_backtrace(), get_called_class());
    $calls[] = Patchwork\Call::create(array_slice(debug_backtrace(), 1));

As demonstrated in the example above, it requires a single argument, which is the output of `debug_backtrace()`. When a `Call` object is instantiated in this way, it represents the **top** frame in the backtrace.

A second argument may also be passed, in which case it is used to populate the "called class" property of the top frame (see "Accessor methods").

### Call::next()

As you may have noted, the whole backtrace is required to construct a `Call` object, even though it only allows to operate on the top frame. All the other frames do not disappear, however. They may be traversed using the `next()` method, which, as the name suggests, returns a `Call` object representing the next stack frame, i.e., the one "below" the current one. This method throws an exception if there is no such frame.

## Attaching multiple filters

Patchwork allows multiple filters to be "attached" to the same function or method. They are called in the same order as the one they were attached in. 

Completing the filtered call has no effect on the execution of further filters, so if no exceptions are thrown, all the filters are executed. However, the limit of a single completion per call still holds:

    Patchwork\filter("Cache::fetch", function($call) {
        $call->complete(false);
    });

    Patchwork\filter("Cache::fetch", function($call) {
        # Throws an exception
        $call->complete(true); 
    });

## Grouping filters

! Still to be written. Do not forget to differentiate from simple sequential attachment, mentioning execution order and automatic handling of instance-specific filters.

## Predefined filters

! Still to be written. Do not forget to mention pattern matching.

### requireInstance
### requireArguments
### requireFirstArguments
### requireUncompleted
### returnValue
### writeArgument
### assertCompleted
### expectCalls
### dispatchNext

## Lifecycle of filters

Generally, filters respond to calls until they are dismissed. This is done by calling the `Patchwork\dismiss()` function on the result of a matching call to `Patchwork\filter()`:

    $filter = Patchwork\filter("Cache::fetch", p\writeArgument(1, "bar"));
    Cache::fetch("foo"); # This call is filtered...
    Patchwork\dismiss($filter);
    Cache::fetch("foo"); # ...and this one is not.

A call to `Patchwork\dismiss()` dismisses only the filter that is referenced by the argument, all other filters are left unchanged.

## PHPUnit integration

Patchwork also offers minimal PHPUnit integration, which is actually nothing more than limiting the scope of filters to a single test method. This is accomplished by the `Patchwork\TestCase` class, which is a specialization of `PHPUnit_Framework_TestCase`. This specialization provides a protected `filter()` method for use instead of `Patchwork\filter()`, and also overrides the `setUp()` and `tearDown()` methods to dismiss any filters attached `TestCase::filter()`.

Example:

    use Patchwork as p;

    class CalculatorTest extends p\TestCase
    {
        function testAdditionOfTwoIntegers()
        {
            # Using TestCase::filter() instead of Patchwork\filter()
            $this->filter("Arithmetic::add", p\group(
                p\requireArguments(1, 2), p\returnValue(3), p\expectCalls(1)
            ));
            $calc = new Calculator;
            $calc->press("1")->press("+")->press("2");
            $this->assertEquals("3", $calc->read());
        }

        function testAdditionOfThreeAndMoreIntegers()
        {
            # In this method, Arithmetic::add() is no longer filtered.
        }
    }

Note that this also means that if you want to override `setUp()` or `tearDown()` while using this specialization, then you have to call `parent::setUp()` and `parent::tearDown()` at some point in the overridden equivalents of these methods.

## Limitations

The greatest limitation of Patchwork is, of course, that only user-defined functions can be filtered. This also extends to cases where user-defined classes inherit internal methods from internal classes. These methods cannot be filtered unless they are overridden.

Unfortunately, this limitation is here to stay, and most probably, for a long time, as intercepting calls to internal functions from userland code is a task of entirely different nature. Catching simple direct calls is probably easy, but as long as we are required to deal with diverse kinds of untestable code, it is also required to catch all kinds of dynamic calls (e.g., through `call_user_func`), handle polymorphism, etc., which, aside from being complicated, would result in a serious performance hit.

If this kind of functionality is needed for you, remember that there is always the [runkit](http://php.net/manual/en/book.runkit.php) extension for that.

One more limitation is that Patchwork does not keep track of which functions it patches. Because of that, you may attach filters to unpatched or entirely inexistent functions without any errors being thrown. However, there are ways to avoid that, and using `Patchwork\expectCalls` is one of them.

## Final notes

If you discover any bugs in Patchwork, please be kind to report them [here](http://github.com/antecedent/patchwork/issues). Note that this also applies to any grammatical errors, factual discrepancies or invalid code samples that may have appeared in this document. Suggestions are definitely welcome as well.

Thanks for your interest!