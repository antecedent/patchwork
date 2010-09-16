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
        $this->tokens_by_type = $this->index_tokens_by_type($this->tokens);
    }
    
    function index_tokens_by_type(array $tokens)
    {
        $tokens_by_type = array();
        foreach ($tokens as $offset => $token) {
            $tokens_by_type[$token[TYPE_OFFSET]][] = $offset;
        }    
        return $tokens_by_type;    
    }
    
    function find_next($type, $offset)
    {
        $ub = upper_bound($this->tokens_by_type[$type], $offset);
        $pos = &$this->tokens_by_type[$type][$ub];
        return isset($pos) ? $pos : INF;
    }
    
    function find_all($type)
    {
        $tokens = &$this->tokens_by_type[$type];
        if (!isset($tokens)) {
            $tokens = array();
        }
        return $tokens;
    }
    
    function splice($splice, $offset, $length = 0)
    {
        if (isset($this->splices[$offset])) {
            throw new \LogicException("Multiple splices at the same offset are not allowed");
        }
        $this->splices[$offset] = $splice;
        $this->splice_lengths[$offset] = $length;
    }
    
    function __toString()
    {
        $string = "";
        $count = count($this->tokens);
        for ($offset = 0; $offset < $count; $offset++) {
            if (isset($this->splices[$offset])) {
                $string .= $this->splices[$offset];
                unset($this->splices[$offset]);
                $offset += $this->splice_lengths[$offset] - 1;
            } else {
                $token = $this->tokens[$offset];
                $string .= isset($token[STRING_OFFSET]) ? $token[STRING_OFFSET] : $token;
            }
        }
        return $string;
    }    
}
