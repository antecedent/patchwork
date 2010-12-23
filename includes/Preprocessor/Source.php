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
    
    public $tokens;
    public $tokensByType;
    public $splices = array();
    public $spliceLengths = array();
    public $code;
    public $file;

    function __construct($tokens)
    {
        if (!is_array($tokens)) {
            $tokens = token_get_all($tokens);
        }
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
        $this->code = null;
    }
    
    function createCodeFromTokens()
    {
        $splices = $this->splices;
        $code = "";
        $count = count($this->tokens);
        for ($offset = 0; $offset < $count; $offset++) {
            if (isset($splices[$offset])) {
                $code .= $splices[$offset];
                unset($splices[$offset]);
                $offset += $this->spliceLengths[$offset] - 1;
            } else {
                $t = $this->tokens[$offset];
                $code .= isset($t[self::STRING_OFFSET]) ? $t[self::STRING_OFFSET] : $t;
            }
        }
        $this->code = $code;
    }

    function __toString()
    {
        if ($this->code === null) {
            $this->createCodeFromTokens();
        }
        return $this->code;
    }
}
