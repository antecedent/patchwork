# Patchwork

## Notice

**Be sure to disable any opcode caches before using Patchwork!** For an explanation, please refer to the "Implementation" section.

## Introduction

### Basics

Patchwork is a library that implements a type of [monkey patching](http://en.wikipedia.org/wiki/Monkey_patch) in PHP. Specifically, it makes it possible to attach _filters_ to user-defined functions and methods:

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

	p\filter(p\returnValue("result"));

Now, if we call the method, our call will be "short-circuited", meaning that only the filter will run, bypassing the method itself:

	Cache::fetch("something"); # => "result"

### Inspecting the Stack Frame

However, this is still not enough for most testing tasks. This is because at times we will need to know how exactly the filtered call was made, including the arguments that were passed, the object on which the method was called, or even the full stack trace.

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

### Dismissing Filters

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

## Limitations

 - Works on user-defined functions only
 - Adds a noticeable performance overhead
 - Does not work on any PHP files that are compiled before Patchwork

## Implementation

As already mentioned, Patchwork employs code preprocessing in order to allow the interception of function calls. The relatively simple preprocessing layer sits on a stream wrapper that overrides the default `file://` protocol. This wrapper is responsible for catching all `include` and `require` operations (and their `_once` counterparts). So, when a file is about to be included, Patchwork preprocesses it and loads it from an in-memory stream instead, which is also why Patchwork may not (and most probably will not) work if an opcode cache is in use.

## Advanced Usage

### Call Matching

### Expectations

### Dealing With Magic Calls

### PHPUnit Integration

### Path Exclusion

## Final Notes