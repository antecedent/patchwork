<?php

use function Patchwork\redefine;

/**
 * @see https://github.com/antecedent/patchwork/issues/114
 */
function foo( $a, ...$args ) {
    echo "\$a is " . var_export( $a, true ) . "\n";
    echo "\$args are " . var_export( $args, true ) . "\n";
}

foo( 1, 2, 3 );

redefine( 'foo', function ( $a, ...$args ) {
    echo "redefined!\n";
    echo "\$a is " . var_export( $a, true ) . "\n";
    echo "\$args are " . var_export( $args, true ) . "\n";
} );

foo( 4, 5, 6 );