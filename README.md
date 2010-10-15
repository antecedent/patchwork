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
	
Any code that is included after this point will be patchable using Patchwork. **Any code that is compiled by PHP earlier, including the script from which Patchwork itself is included, will not**.

### Basics

Patchwork is a library that implements a kind of [monkey patching](http://en.wikipedia.org/wiki/Monkey_patch) in PHP: it makes it possible to attach _filters_ to user-defined functions and methods:

	Patchwork\filter("Cache::fetch", function() {
		echo "Fetching something from cache\n";
	});

These filters, like the one in the example above, always run before the function they are attached to. This happens every time the function is called:

	$result = Cache::fetch("something"); # prints "Fetching something from cache"

Any valid PHP callback will work as a filter, but since lambdas have finally arrived in PHP 5.3, there is rarely a reason not to use them for this purpose. Additionaly, Patchwork provides some ready-made filters for more expressive power:

	use Patchwork as p;
	p\filter("Cache::fetch", p\say("Fetching something from the cache\n"));
	
Some of these filters may be left unmentioned in this document, but they can always be looked up by viewing the source of `Patchwork.php`.

### Short-Circuiting

Now, we shall make the filter do something actually useful:

	Patchwork\filter("Cache::fetch", function($call) {
		$call->complete("result");
	});

This time, it essentially stubs out the method by making it _return_ the string "result" unconditionally. This comes really handy in the context of unit testing, where complex dependencies have to be replaced with test doubles.

And of course, there is also a predefined filter for that:

	p\filter("Cache::fetch", p\returnValue("result"));

Now, if we call the method, our call will be "short-circuited", meaning that only the filter will run, bypassing the method itself:

	Cache::fetch("something"); # => "result"

### Inspecting the Stack Frame

Simply assigning a constant result to a method is still not enough for most testing tasks. This is because at times we will need to know how exactly the filtered call was made, including the arguments that were passed, the object on which the method was called, or even the full stack trace.

That is why each filter receives a `Patchwork\Call` object as an argument. This object is essentially a wrapper for the result of `debug_backtrace`. It represents a single stack frame, but also allows to access the ones "below" it using the `next()` method.

Also, all the properties of a stack frame that are populated by `debug_backtrace` are available as public fields of the `Patchwork\Call` class:

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

If a filter is no longer needed, it can be dismissed:

	$handle = Patchwork\filter("Cache::fetch", Patchwork\returnValue(42));
	Patchwork\dismiss($handle);

### Attaching Multiple Filters

It is possible to attach multiple filters to the same function:

	$first  = Patchwork\filter("Cache::fetch", Patchwork\say("Hello World!"));
	$second = Patchwork\filter("Cache::fetch", Patchwork\returnValue(42));
	
As a result, they can now be dismissed independently:

	Patchwork\dismiss($first);
	Patchwork\dismiss($second);

Note that a call to `Patchwork\Call::complete()` does not affect the execution of further filters. However, this method may only be called once. After a call has been completed, attempting to complete it again results in a `Patchwork\Exceptions\MultipleCallCompletions` exception being thrown.

## Purpose

Patchwork is primarily meant for dealing with hardly testable codebases which still need to be tested. It is a much lower-level solution than most test double frameworks, but as a result, it is also much more flexible. Using Patchwork, it is possible to intercept calls to any user-defined method or function, even static, private and final ones. Because of this, singletons and other non-polymorphic dependencies are no longer a problem in testing.

## Limitations

 - Works on user-defined functions only
 - Adds a noticeable performance overhead
 - Cannot filter calls to anything that is defined before including `Patchwork.php`

## Implementation

As already mentioned, Patchwork employs code preprocessing in order to allow the interception of function calls. The relatively simple preprocessing layer sits on a stream wrapper that overrides the default `file://` protocol. This wrapper is responsible for catching all `include` and `require` operations (and their `_once` counterparts). So, when a file is about to be included, Patchwork preprocesses it and loads it from an in-memory stream instead, which is also why Patchwork may not (and most probably will not) work if an opcode cache is in use.

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
	
Now, we shall rewrite the code above in a more idiomatic way, using filter chains:

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

Any filter that appears in a filter chain may "break" it by calling `Patchwork\breakChain()`. Breaking it forbids any remaining chained filters from being applied to the filtered call. This is how the built-in `requireArgs` filter works: if the arguments of the filtered call do not match the prespecified ones, it breaks the filter chain, so in the example above, neither `p\say()` nor `p\returnValue()` runs. Because of that, this filter, along with all the `require*` family, should not be used outside filter chains.

### Setting Expectations

To make sure that a call has been filtered, we can set a call count expectation:

	use Patchwork as p;
	
	# Expect exactly one call with "foo" as the argument:
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("foo")),
		p\expect(1), 
		p\returnValue("a value")
	));
	
	# Expect one to three calls to Cache::filter("bar"):
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("bar")),
		p\expect(1, 3), 
		p\returnValue("another value")
	));
	
	# Expect at least 2 calls to Cache::filter("baz"):
	p\filter("Cache::filter", p\chain(
		p\requireArgs(array("baz")),
		p\expect(2, INF),
		p\returnValue("another value")
	));
	
	# Expect no calls to Cache::filter with any other arguments:
	p\filter("Cache::filter", p\expect(0));

The filter returned by `Patchwork\expect` counts how many calls it intercepts, and if that number does not fall into the expected range, it throws a `Patchwork\Exceptions\UnmetCallCountExpectation` exception. Note, however, that this might not happen until all references to this filter are lost.

### PHPUnit Integration

Since Patchwork uses global variables to store the filters and its own preprocessing callbacks, it breaks the global state backup feature of PHPUnit. One solution to this is manually blacklisting these variables:

	class TestCase extends PHPUnit_Framework_TestCase
	{
		protected $backupGlobalsBlacklist = array(
	        Patchwork\Filtering\FILTERS,
	        Patchwork\Preprocessing\PREPROCESSORS,
	    );
    }
    
However, for maximum convenience, Patchwork also provides a ready-made specialized class named `Patchwork\TestCase` that has the blacklist already overriden. In addition, it contains a `filter` method, which is essentially an alias for `Patchwork\filter`, except that all filters attached using this alias are automatically dismissed in the `tearDown` method:

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

If we do not want a certain file or directory to be preprocessed, we can instruct Patchwork to exclude it:

	Patchwork\exclude("/home/user/a-php-library/"); # do not forget the trailing slash!
	Patchwork\exclude("/home/user/another-php-library/file.php");

## Final Notes

If you happen to discover any bugs in Patchwork, please do not hesitate to report them [here](http://github.com/antecedent/patchwork/issues). Note that this also applies to any grammatical errors, factual discrepancies or invalid code samples that may have appeared in this document. Suggestions are definitely welcome as well.

Thanks for your interest!