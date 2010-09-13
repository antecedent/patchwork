<?php

namespace Patchwork;

const SEMICOLON = ";";

const LEFT_CURLY_BRACKET  = "{";
const RIGHT_CURLY_BRACKET = "}";

const LEFT_SQUARE_BRACKET  = "[";
const RIGHT_SQUARE_BRACKET = "]";

const LEFT_PARENTHESIS  = "(";
const RIGHT_PARENTHESIS = ")";

const TYPE_OFFSET = 0;
const STRING_OFFSET = 1;

class Source
{
    var $tokens = array();
    var $tokens_by_type;
    var $splices = array();
    var $splice_lengths = array();
    
    function __construct($tokens)
    {
        $this->tokens = $tokens;
        $this->tokens_by_type = index_tokens_by_type($this->tokens);
    }
}

function index_tokens_by_type(array $tokens)
{
    $tokens_by_type = array();
    foreach ($tokens as $offset => $token) {
        $tokens_by_type[$token[TYPE_OFFSET]][] = $offset;
    }    
    return $tokens_by_type;    
}

function find_next($type, $offset, Source $s)
{
    $ub = upper_bound($s->tokens_by_type[$type], $offset);
    $pos = &$s->tokens_by_type[$type][$ub];
    return isset($pos) ? $pos : INF;
}

function find_all($type, Source $s)
{
    $tokens = &$s->tokens_by_type[$type];
    if (!isset($tokens)) {
        $tokens = array();
    }
    return $tokens;
}

function upper_bound(array $array, $value)
{
    $count = count($array);
    $first = 0;
    while ($count > 0) {
        $i = $first;
        $step = $count >> 1;
        $i += $step;
        if ($value >= $array[$i]) {
               $first = ++$i; 
               $count -= $step + 1;
          } else {
              $count = $step;
          }
    }
    return $first;    
}    

function splice($splice, $offset, $length, Source $s)
{
    if (isset($s->splices[$offset])) {
        throw new \LogicException("Multiple splices at the same offset are not allowed");
    }
    $s->splices[$offset] = $splice;
    $s->splice_lengths[$offset] = $length;
}

function source_to_string(Source $s)
{
    $string = "";
    $count = count($s->tokens);
    for ($offset = 0; $offset < $count; $offset++) {
        if (isset($s->splices[$offset])) {
            $string .= $s->splices[$offset];
            unset($s->splices[$offset]);
            $offset += $s->splice_lengths[$offset] - 1;
        } else {
            $token = $s->tokens[$offset];
            $string .= isset($token[STRING_OFFSET]) ? $token[STRING_OFFSET] : $token;
        }
    }
    return $string;
}
