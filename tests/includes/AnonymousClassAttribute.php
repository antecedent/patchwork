<?php

#[\Attribute]
class SomeAttribute {
    public function __construct($value) {}
}

// Simple attribute
new #[SomeAttribute('foo')] class {};

// Several attributes
new #[SomeAttribute('foo')] #[SomeAttribute('bar')] class {};

// Attribute with argument on several lines
new #[SomeAttribute([
    'foo',
    'bar',
])] class {};
