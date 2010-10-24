# Patchwork

### Version 0.1

An implementation of [monkey patching](http://en.wikipedia.org/wiki/Monkey_patching) for PHP, written in pure userland PHP 5.3 code and available under the terms of [the MIT license](http://en.wikipedia.org/wiki/MIT_License).

## Introduction

Patchwork is a library that allows calls to user-defined PHP functions and methods to be **intercepted** and run through **filters**.  Filters can be bound to functions using the `Patchwork\filter` function. Any PHP callback can be used as a filter. Filters are allowed, but not obliged to respond to the call before letting it reach its actual destination.

This functionality can be put into use in various tasks that involve testing problematic (hardly testable) code. By using filters, we can entirely bypass the function they are attached to, essentially redefining it. This allows us to stub out any user-defined function or method, even if it is static, final or private.

Such practices often go by the name of [monkey patching](http://en.wikipedia.org/wiki/Monkey_patching). They are quite widespread among the communities of some dynamic languages, for example, Ruby and Python, but not so popular in the PHP universe. This is mostly because there is no easy way to redefine a function in PHP. Nevertheless, it is still possible with the help of such core extensions as [runkit](http://php.net/manual/en/book.runkit.php) or [php-test-helpers](http://github.com/sebastianbergmann/php-test-helpers), or by preprocessing code on the fly, which is the exact solution employed by Patchwork.

## Getting Started

First of all, we have to include `Patchwork.php`:

	require __DIR__ . `/patchwork/Patchwork.php`
	
Any functions defined beyond this point will be able to have filters attached to them. Any functions defined before **(or in the script from which `Patchwork.php` is included)** will, however, be not.

## Examples

### Function Stubbing

Here is how we would stub out a `getInstance` method of a singleton class using Patchwork:

	Patchwork\filter("Database::getInstance", function($call) use ($fakeDb) {
		$call->respondWith($fakeDb);
	});
	
We manipulate the filtered call through a [Patchwork\Call](http://github.com/antecedent/patchwork/blob/0.1/classes/Call.php) object, which is received by every filter as the only argument. Calling the `respondWith` method on it assigns the call a return value. This can be done only once for each call.

If `respondWith` or `respondWithReference` (or `respond`, which is a shortcut for `respondWith(null)`) is called at the filtering stage of a call, then the call will terminate right after this stage, never reaching its actual destination:

	function printFoo()
	{
		print "foo";
	}
	
	$filter = Patchwork\filter("printFoo", function($call) {
		$call->respond();
	});
	
	# Nothing is printed
	printFoo();

Otherwise, if none of those methods are called, the call will continue as usual:

	# Get rid of the previous filter
	Patchwork\dismiss($filter);
	
	Patchwork\filter("printFoo", function() {
		print "Filtered: ";
	});
	
	# Prints "Filtered: foo"
	printFoo(); 

### Predefined Filters

Patchwork provides some ready-made filters for the most common use cases. For example, the first example, which involves stubbing out a `getInstance` method, could be rewritten this way:

	Patchwork\filter("Database::getInstance", Patchwork\respondWith($fakeDb));
	
All these built-in filters (or, more precisely, functions that return them) are defined and documented in [Patchwork.php](http://github.com/antecedent/patchwork/blob/0.1/Patchwork.php).

### Filter Chaining

It is possible to attach more than one filter in a single call to `Patchwork\filter`:

	use Patchwork as p;
	p\filter("Post::findById", p\matchArgs(1), p\respondWith($fakePost));
	
This way, we create a **filter chain** consisting of two filters. Each member of a filter chain is allowed to **block** the execution of filters that appear in the same chain after it.

This feature can be used to control the execution of other filters depending on various criteria. For example, in the above code snippet, we use the `matchArgs` built-in filter to limit the stubbed result (`$fakePost`) to cases when the `findById` method is called with the argument `1`.

If we wanted to write a **blocking** filter ourselves, we could use `Patchwork\block()` to issue a blocking signal from inside a filter. Internally, these "signals" are implemented as exceptions, so they terminate the currently executing filter immediately after being issued.

### Multiple Filter Chains

Aside from being chainable, filters (as well as filter chains) can also be attached through separate `Patchwork\filter` calls. This allows us to react to calls differently depending on some conditions, such as the arguments:

	use Patchwork as p;
	
	p\filter("Post::findById", p\matchArgs(1), p\respondWith($fakePost));
	p\filter("Post::findById", p\matchArgs(2), p\respondWith($anotherFakePost));
	
	# If none of the above filters responds...
	p\filter("Post::findById", p\matchNoResponse(), p\throwException(new PostNotFound));
	
### Expectations

Using the built-in **call count expectation** filter (`Patchwork\expectCalls`), it is possible to expect a function to be called a specific number of times:

	use Patchwork as p;

	# Expect 1 to 3 calls to Post::findById(1)
	$first = p\filter("Post::findById",
	                  p\matchArgs(1),
	                  p\expectCalls(1, 3),
	                  p\respondWith($post));
	
	# Expect at least 2 calls to Post::findById(17)
	$second = p\filter("Post::findById",
	                   p\matchArgs(17),
	                   p\expectCalls(2, INF),
	                   p\respondWith($anotherPost));
	
	# Expect exactly 4 calls to Post::findById in total
	$third = p\filter("Post::findById", p\expectCalls(4));
	
	# Make 4 calls to Post::findById(1), but no calls to Post::findById(17)
	Post::findById(1); # OK
	Post::findById(1); # OK
	Post::findById(1); # OK
	Post::findById(1); # Error: too many calls
	
	p\dismiss($second); # Error: too few calls
	p\dismiss($third);  # OK

### PHPUnit Integration

As you may have already noticed, when filters are no longer needed, they can be dismissed by passing the result of `Patchwork\filter()` to `Patchwork\dismiss()`. However, this becomes a very tedious task in unit tests, where each test method may need a different set of filters.

So, to solve this, Patchwork provides a [specialization](http://github.com/antecedent/patchwork/blob/0.1/classes/TestCase.php) of the `PHPUnit_Framework_TestCase` class, which includes a `filter` method. This method is essentially an alias for `Patchwork\filter`, except that each filter attached using this alias is automatically dismissed after each test method, that is, in the `tearDown` stage.

## Final Notes

If you happen to discover any bugs in Patchwork, please do not hesitate to report them here. This also applies to any grammatical errors, factual discrepancies or invalid code samples that may have appeared in this document. And, of course, suggestions are welcome as well.

Thank you for your interest!
