<?php

namespace Patchwork\Preprocessing;

use Patchwork\Common;
use Patchwork\Exception;

class Source
{
    const TYPE_OFFSET = 0;
    const STRING_OFFSET = 1;    
    
    public $tokens = array();
    public $tokens_by_type;
    public $splices = array();
    public $splice_lengths = array();
    
    function __construct($tokens)
    {
        $this->tokens = $tokens;
        $this->tokens_by_type = $this->index_tokens_by_type($this->tokens);
    }
    
    function index_tokens_by_type(array $tokens)
    {
        $tokens_by_type = array();
        foreach ($tokens as $offset => $token) {
            $tokens_by_type[$token[self::TYPE_OFFSET]][] = $offset;
        }
        return $tokens_by_type;
    }
    
    function find_next($type, $offset)
    {
        if (!isset($this->tokens_by_type[$type])) {
            return INF;
        }
        $bound = Common\get_upper_bound($this->tokens_by_type[$type], $offset);
        $pos = &$this->tokens_by_type[$type][$bound];
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
            throw new Exceptions\MultipleSourceSplices;
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
                $t = $this->tokens[$offset];
                $string .= isset($t[self::STRING_OFFSET]) ? $t[self::STRING_OFFSET] : $t;
            }
        }
        return $string;
    }
}

