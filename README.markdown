# Patchwork

## NOTICE

Patchwork is currently in an early stage of development. Many of the features described below are not yet implemented.

## Introduction

Making code testable in PHP is an unusually hard task when compared to other dynamic languages. There are hardly any possibilities for metaprogramming, for example, you can't redefine functions (although the `runkit` extension allows this) or manipulate class definitions at runtime. 

This doesn't mean that it's impossible to write testable code in PHP. However, it requires quite a lot of possibly time-consuming engineering decisions, which in some cases may even distract from more important implementation details. Also, sometimes one might simply run into a large codebase with testability issues, where refactoring is not an acceptable option.

However, PHP is still a dynamic language. It allows for things like preprocessing code at runtime (before including it, of course), which is what Patchwork does. This solution is somewhat hackish, but that does pay off when dealing with otherwise untestable code.

By preprocessing code, it finally becomes possible to replace functions and methods. However, that's not the exact solution used by Patchwork. As the name suggests, Patchwork *patches* functions, that is, prepends some code to them to allow for call interception.

## Basic usage

Let's say there is a function named `foo`:

    function foo()
    {
        return 1;
    }

Using Patchwork, one may *listen* to all calls this function receives:

    Patchwork\listen("foo", function() {
        return 2;
    });

Now, if we called `foo()`, we would receive `2` as the result, because the listener closure would *intercept* the call and provide its own return value.

The listener doesn't have to be a closure. All callable values are accepted.

For example, this code essentially replaces the function `foo` with the function `bar`:

    Patchwork\listen("foo", "bar");

TODO methods

## Skipping and dismissing listeners

TODO

## Patching the code

TODO

## Use cases

TODO

## Known issues

TODO

## Final notes

TODO