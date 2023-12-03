<?php

$foo = 'bar';

// Notably, PHP's tokenizer splits the below string into a double-quote token, 
// then a T_ENCAPSED_AND_WHITESPACE token, and then some others as well.
echo "We are in a T_ENCAPSED_AND_WHITESPACE, and not in a $foo.\n";