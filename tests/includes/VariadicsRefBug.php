<?php

use function Patchwork\redefine;

/**
 * @see https://github.com/antecedent/patchwork/issues/115
 */
function foo( &$a, &...$args ) {
    $a += 1;
    foreach ( $args as &$v ) {
        $v += 2;
    }
}

$a=1; $b=2; $c=3;
foo( $a, $b, $c );
echo "a=$a b=$b c=$c\n";

redefine( 'foo', function ( &$a, &...$args ) {
    $a += 10;
    foreach ( $args as &$v ) {
        $v += 20;
    }
} );

$a=4; $b=5; $c=6;
foo( $a, $b, $c );
echo "a=$a b=$b c=$c\n";