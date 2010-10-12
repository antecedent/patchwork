# Patchwork

Patchwork is a library that allows [monkey patching](http://en.wikipedia.org/wiki/Monkey_patch) in PHP. Patchwork is implemented in pure userland PHP 5.3 code and does not depend on any non-standard extensions.

## Notice

**Be sure to disable any opcode caches before using Patchwork!** For an explanation, please refer to the "Implementation" section.

## Purpose

Patchwork aims to bring the possibility of so-called [monkey patching](http://en.wikipedia.org/wiki/Monkey_patch) into the PHP universe. Of course, while it is only a matter of taste as to if monkey patching is an acceptable practice in general, it appears that up to now, there had been no serious experiments to show if it could be implemented in PHP at all, so this is exactly what Patchwork tries to accomplish.

Speaking of monkey patching itself, it is quite a common practice in many dynamic languages, because it brings considerable ease to testing-related tasks. In both unit and functional tests, it is extremely convenient to simply redefine a function, a method or a whole class at runtime, because this allows us to replace it with a test double without any changes to the client code.

Unfortunately, in the world of PHP, it is not that simple. The default setup of the Zend Engine simply does not allow us to redefine any of these entities at runtime. So, this leaves us with the only option of altering code _before compile-time_. Now, given that (at least from a certain standpoint) PHP is still an _interpreted_ language, this is not only possible but can also be made completely transparent, because an already running piece of PHP code may "include" a file containing another such piece, triggering compilation again. The only thing to do here is to catch all these "includes" and preprocess them before they are actually parsed and compiled.

## Implementation

### Preprocessing

### Call Filtering

## Examples

### Pattern Matching

### Dealing With Magic Calls

### PHPUnit Integration

### Path Exclusion

## Known Limitations

## Final Notes