# Patchwork

Probably the first userland implementation of monkey patching in PHP. Released under [the MIT license](http://www.opensource.org/licenses/mit-license.html).

## Notice

**Be sure to disable any opcode caches before using Patchwork!** For an explanation, please refer to the "Implementation" section.

## Introduction

### Requirements

Patchwork is written in pure userland PHP 5.3 code. It does not require any non-standard PHP extensions.

### Getting started

To start using Patchwork, we have to include `Patchwork.php`:

	require __DIR__ . "/patchwork/Patchwork.php";
	
Any code that is included after this step will be patchable using Patchwork. **Any code that has been imported earlier, including the script from which Patchwork itself has been included, will, however, be not**.

### Basics

Patchwork is a library that implements a kind of [monkey patching](http://en.wikipedia.org/wiki/Monkey_patch) in PHP: it makes it possible to attach **filters** to user-defined functions and methods:

	Patchwork\filter("Cache::fetch", function() {
		echo "Fetching something from cache\n";
	});

These filters, like the one in the example above, always run before the function they are attached to. This happens every time the function is called:

	$result = Cache::fetch("something"); # prints "Fetching something from cache"

Any valid PHP callback will work as a filter, but since lambdas have finally arrived in PHP 5.3, there is rarely a reason not to use them for this purpose. Additionaly, Patchwork provides some ready-made filters for more expressive power:

	use Patchwork as p;
	p\filter("Cache::fetch", p\say("Fetching something from the cache\n"));

Only some of these built-in filters will be mentioned in this document, the rest are left to the reader to discover by browsing the source of `Patchwork.php`, where they are also documented individually.

### Short-Circuiting

Now, we shall make the filter do something actually useful:

	Patchwork\filter("Cache::fetch", function($call) {
		$call->complete("result");
	});

This time, it essentially stubs out the method by making it **return** the string "result" unconditionally. This comes in handy in the context of unit testing, where complex dependencies have to be replaced with test doubles.

And of course, there is also a predefined filter for that:

	p\filter("Cache::fetch", p\returnValue("result"));

Now, if we call the method, our call will be "short-circuited", meaning that only the filter will run, bypassing the method itself:

	Cache::fetch("something"); # => "result"

### Inspecting the Stack Frame

Simply assigning a constant result to a method is still not enough for most testing tasks. This is because at times we will need to know how exactly the filtered call was made, including the arguments that were passed, the object on which the method was called, or even the full stack trace.

That is why each filter receives a `Patchwork\Call` object as an argument. This object is essentially a wrapper for the result of `debug_backtrace`. It represents a single stack frame, but also allows to access the ones "below" it using the `next()` method.

Also, all the properties of a stack frame that are populated by `debug_backtrace` are also available as public fields of the `Patchwork\Call` class:

	Patchwork\filter("Cache::fetch", function(Patchwork\Call $call) {
		
		$call->function; # => "fetch"
		$call->class;    # => "Cache"
		$call->object;   # => null (if the call is static)
		$call->file;     # ...
		$call->line;     # ...
		$call->type;     # => "::" (if the call is static)
		$call->args;     # => array("something", null)
		
		# Retrieves the stack frame from which the filtered call was made
		$call->next();
		$call->next()->function; # ...
		
		# Reference arguments can be updated from the filter
		$call->args[1] = true; # Mind zero-based indexing!
		
		# See "Short-Circuiting"
		$call->complete("result");
		
	});

### Dismissing a Filter

When a filter is no longer needed, it can be dismissed:

	$handle = Patchwork\filter("Cache::fetch", Patchwork\returnValue(42));
	Patchwork\dismiss($handle);

### Attaching Multiple Filters

It is possible to attach multiple filters to the same function and have them execute in the order of attachment:

	$first  = Patchwork\filter("Cache::fetch", Patchwork\say("Hello "));
	$second = Patchwork\filter("Cache::fetch", Patchwork\say("World!"));
	
	Cache::fetch("something"); # prints "Hello World!"

Also, these filters can now be dismissed independently:

	Patchwork\dismiss($first);
	Patchwork\dismiss($second);

Note that a call to `Patchwork\Call::complete()` does not affect the execution of further filters, so Patchwork always attempts to execute all relevant filters. However, this method may only be called once. After a call has been completed, attempting to complete it again results in a `Patchwork\Exceptions\CallAlreadyCompleted` exception being thrown.

## Purpose

Patchwork is primarily meant for dealing with hardly testable codebases that still need to be tested. It is a much lower-level solution than most stubbing or mocking frameworks, but as a result, it is also much more flexible. Using Patchwork, it is possible to intercept calls to any user-defined method or function, even a static, private or final one. This makes it significantly easier to overcome various testability obstacles, such as singletons and other kinds of non-polymorphic dependencies.

## Implementation

Patchwork employs code preprocessing in order to allow the interception of function calls. The relatively simple preprocessing layer sits on a stream wrapper that overrides the default `file://` protocol. This wrapper is responsible for catching all `include` and `require` operations (and their `_once` counterparts). So, when a file is about to be included, Patchwork preprocesses it and loads it from an in-memory stream instead, which is also why Patchwork may not (and most probably will not) work if an opcode cache is in use.

## Limitations

Without a doubt, the greatest limitation of Patchwork is that it cannot be applied to internal PHP functions. Unfortunately, this shortcoming is here to stay, because it is simply impossible to inject filtering logic into core PHP code at runtime, which is the way Patchwork works. But it should never be forgotten that there is always the [runkit](http://php.net/manual/en/book.runkit.php) extension for that, as well as [a newer solution](http://github.com/sebastianbergmann/php-test-helpers) by Sebastian Bergmann and Johannes Schlüter.

Also, another obvious drawback is that such an implementation adds a certain performance overhead. However, in testing environments, to which Patchwork is mainly targeted, this overhead should be low enough to go unnoticed.

## Advanced Usage

### Matching Calls Using Filter Chains

By inspecting the stack frame, it is possible to make the filter react differently depending on arguments or any other properties of the filtered call:

	Patchwork\filter("Cache::filter", function($call) {
		switch ($call->args[0]) {
			case "foo":
				echo "Fetching foo from cache";
				return $call->complete("a value");
			case "bar":
				return $call->complete("another value");
			default:
				# Prevent interaction with the actual Cache::filter method
				throw new InvalidArgumentException;
		}
	});
	
Now, we shall rewrite the code above in a more idiomatic way, using **filter chains**:

	use Patchwork as p;
	
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("foo")),
		p\say("Fetching foo from cache"),
		p\returnValue("a value")
	));
	
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("bar")),
		p\returnValue("another value")
	));
	
	p\filter("Cache::filter", p\assertCompleted());

Any filter that appears in a filter chain is allowed to "break" it at any time by calling `Patchwork\breakChain()`. Breaking it forbids any remaining filters **in the same chain** from being applied to the currently filtered call.

The built-in `requireArgs` filter, along with the whole `Patchwork\require*` family, is specifically meant for use in chains. As the name suggests, it checks if the arguments of the filtered call match the prespecified ones, and if they do not, it breaks the filter chain. In the example above, this results in neither `p\say` nor `p\returnValue` being executed in such cases (do not forget that filters are always executed in the same order they are attached in).

### Setting Expectations

To make sure that a filtered function has been called a specific number of times, we can set a call count expectation:

	use Patchwork as p;
	
	# Expect one to three calls with "foo" as the argument:
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("foo")),
		p\expectCalls(1, 3), 
		p\returnValue("a value")
	));
	
	# Expect at least two calls to Cache::filter("bar"):
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("bar")),
		p\expectCalls(2, INF), 
		p\returnValue("another value")
	));
	
	# Expect no calls to Cache::filter with any other arguments:
	p\filter("Cache::filter", p\chain(
		p\requireUncompleted(),
		p\expectCalls(0)
	);

The filter returned by `Patchwork\expectCalls` counts how many calls it intercepts, and if that number does not fall into the prespecified range, it throws a `Patchwork\Exceptions\UnmetCallCountExpectation` exception. Note, however, that this might not happen until all references to this filter have been lost.

Also note that because of the nature of filter chains, the exact position of the `Patchwork\expectCalls` filters makes a great difference. For example, if we had placed them before `requireArgs` instead, they would not be argument-specific anymore and would therefore count every call to `Cache::fetch`.

### PHPUnit Integration

Since Patchwork uses global variables to store the filters and its own preprocessing callbacks, it breaks the global state backup feature of [PHPUnit](http://www.phpunit.de/). One solution to this is to manually blacklist these variables:

	class TestCase extends PHPUnit_Framework_TestCase
	{
		protected $backupGlobalsBlacklist = array(
	        Patchwork\Filtering\FILTERS,
	        Patchwork\Preprocessing\PREPROCESSORS,
	    );
    }
    
However, for maximum convenience, Patchwork also provides a ready-made specialization of the `PHPUnit_Framework_TestCase` class, which has the blacklist already overridden. In addition, it contains a `filter` method, which is essentially an alias for `Patchwork\filter`, except that all filters attached using this alias are automatically dismissed in the `tearDown` method:

	class TestCase extends Patchwork\TestCase
	{
		static function throwAnException()
		{
			throw new Exception;
		}
		
		function testForTheFirstTime()
		{
			$this->filter("TestCase::throwAnException", Patchwork\returnValue(null));
			self::throwAnException(); # nothing thrown here!
		}
		
		/**
		 * @expectedException Exception
		 */
		function testAgain()
		{
			self::throwAnException(); # the filter is no longer in effect
		}
	}

### Path Exclusion

In cases when a certain file or directory should be ignored by the preprocessor, Patchwork can be instructed to exclude it:

	Patchwork\exclude("/home/user/a-php-library/"); # do not forget the trailing slash!
	Patchwork\exclude("/home/user/another-php-library/file.php");

## Final Notes

If you happen to discover any bugs in Patchwork, please do not hesitate to report them [here](http://github.com/antecedent/patchwork/issues). This also applies to any grammatical errors, factual discrepancies or invalid code samples that may have appeared in this document. And, of course, suggestions are welcome as well.

Thank you for your interest!