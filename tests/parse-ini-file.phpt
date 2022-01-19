--TEST--
parse_ini_file isn't broken.

--FILE--
<?php

require __DIR__ . "/../Patchwork.php";

file_put_contents( __DIR__ . "/parse-ini-file.ini", <<<EOF
; This is an ini file.
; This comment has <?php and ?> in it.
foo = bar
EOF
);

$ini = parse_ini_file( __DIR__ . "/parse-ini-file.ini" );
assert( $ini === array( "foo" => "bar" ) );
?>
===DONE===

--CLEAN--
<?php
unlink( __DIR__ . "/parse-ini-file.ini" );
?>
--EXPECT--
===DONE===
