<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 * @link       http://github.com/antecedent/patchwork
 */
namespace Patchwork\Preprocessor;

use Patchwork\Utils;
use Patchwork\Exceptions;

class Source
{
    const TYPE_OFFSET = 0;
    const STRING_OFFSET = 1;    
    
    public $tokens = array();
    public $tokensByType;
    public $splices = array();
    public $spliceLengths = array();
    
    function __construct($tokens)
    {
        $this->tokens = $tokens;
        $this->tokensByType = $this->indexTokensByType($this->tokens);
    }
    
    function indexTokensByType(array $tokens)
    {
        $tokensByType = array();
        foreach ($tokens as $offset => $token) {
            $tokensByType[$token[self::TYPE_OFFSET]][] = $offset;
        }
        return $tokensByType;
    }
    
    function findNext($type, $offset)
    {
        if (!isset($this->tokensByType[$type])) {
            return INF;
        }
        $bound = Utils\getUpperBound($this->tokensByType[$type], $offset);
        $pos = &$this->tokensByType[$type][$bound];
        return isset($pos) ? $pos : INF;
    }
    
    function findAll($type)
    {
        $tokens = &$this->tokensByType[$type];
        if (!isset($tokens)) {
            $tokens = array();
        }
        return $tokens;
    }
    
    function splice($splice, $offset, $length = 0)
    {
        $this->splices[$offset] = $splice;
        $this->spliceLengths[$offset] = $length;
    }
    
    function __toString()
    {
        $string = "";
        $count = count($this->tokens);
        for ($offset = 0; $offset < $count; $offset++) {
            if (isset($this->splices[$offset])) {
                $string .= $this->splices[$offset];
                unset($this->splices[$offset]);
                $offset += $this->spliceLengths[$offset] - 1;
            } else {
                $t = $this->tokens[$offset];
                $string .= isset($t[self::STRING_OFFSET]) ? $t[self::STRING_OFFSET] : $t;
            }
        }
        return $string;
    }
}

