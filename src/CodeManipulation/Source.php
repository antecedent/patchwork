<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2017 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation;

use Patchwork\Utils;

class Source
{
    const TYPE_OFFSET = 0;
    const STRING_OFFSET = 1;

    const PREPEND = 'PREPEND';
    const APPEND = 'APPEND';
    const OVERWRITE = 'OVERWRITE';

    const ANY = null;

    public $tokens;
    public $tokensByType;
    public $splices;
    public $spliceLengths;
    public $code;
    public $file;
    public $matchingBrackets;
    public $levels;
    public $levelBeginnings;
    public $levelEndings;
    public $tokensByLevel;
    public $tokensByLevelAndType;

    function __construct($string)
    {
        $this->code = $string;
        $this->initialize();
    }

    function initialize()
    {
        $this->tokens = token_get_all($this->code);
        $this->tokens[] = [T_WHITESPACE, ""];
        $this->indexTokensByType();
        $this->collectBracketMatchings();
        $this->collectLevelInfo();
        $this->splices = [];
        $this->spliceLengths = [];
    }

    function indexTokensByType()
    {
        $this->tokensByType = [];
        foreach ($this->tokens as $offset => $token) {
            $this->tokensByType[$token[self::TYPE_OFFSET]][] = $offset;
        }
    }

    function collectBracketMatchings()
    {
        $this->matchingBrackets = [];
        $stack = [];
        foreach ($this->tokens as $offset => $token) {
            $type = $token[self::TYPE_OFFSET];
            switch ($type) {
                case '(':
                case '[':
                case '{':
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                    $stack[] = $offset;
                    break;
                case ')':
                case ']':
                case '}':
                    $top = array_pop($stack);
                    $this->matchingBrackets[$top] = $offset;
                    $this->matchingBrackets[$offset] = $top;
                    break;
            }
        }
    }

    function collectLevelInfo()
    {
        $level = 0;
        $this->levels = [];
        $this->tokensByLevel = [];
        $this->levelBeginnings = [];
        $this->levelEndings = [];
        $this->tokensByLevelAndType = [];
        foreach ($this->tokens as $offset => $token) {
            $type = $token[self::TYPE_OFFSET];
            switch ($type) {
                case '(':
                case '[':
                case '{':
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                    $level++;
                    Utils\appendUnder($this->levelBeginnings, $level, $offset);
                    break;
                case ')':
                case ']':
                case '}':
                    Utils\appendUnder($this->levelEndings, $level, $offset);
                    $level--;
            }
            $this->levels[$offset] = $level;
            Utils\appendUnder($this->tokensByLevel, $level, $offset);
            Utils\appendUnder($this->tokensByLevelAndType, [$level, $type], $offset);
        }
        Utils\appendUnder($this->levelBeginnings, 0, -1);
        Utils\appendUnder($this->levelEndings, 0, count($this->tokens));
    }

    function has($types)
    {
        foreach ((array) $types as $type) {
            if ($this->all($type) !== []) {
                return true;
            }
        }
        return false;
    }

    function is($types, $offset)
    {
        foreach ((array) $types as $type) {
            if ($this->tokens[$offset][self::TYPE_OFFSET] === $type) {
                return true;
            }
        }
        return false;
    }

    function skip($types, $offset, $direction = 1)
    {
        $offset += $direction;
        $types = (array) $types;
        while ($offset < count($this->tokens) && $offset >= 0) {
            if (!in_array($this->tokens[$offset][self::TYPE_OFFSET], $types)) {
                return $offset;
            }
            $offset += $direction;
        }
        return ($direction > 0) ? INF : -1;
    }

    function skipBack($types, $offset)
    {
        return $this->skip($types, $offset, -1);
    }

    function within($types, $low, $high)
    {
        $result = [];
        foreach ((array) $types as $type) {
            $candidates = isset($this->tokensByType[$type]) ? $this->tokensByType[$type] : [];
            $result = array_merge(Utils\allWithinRange($candidates, $low, $high), $result);
        }
        return $result;
    }

    function read($offset, $count = 1)
    {
        $result = '';
        $pos = $offset;
        while ($pos < $offset + $count) {
            if (isset($this->tokens[$pos][self::STRING_OFFSET])) {
                $result .= $this->tokens[$pos][self::STRING_OFFSET];
            } else {
                $result .= $this->tokens[$pos];
            }
            $pos++;
        }
        return $result;
    }

    function siblings($types, $offset)
    {
        $level = $this->levels[$offset];
        $begin = Utils\lastNotGreaterThan(Utils\access($this->levelBeginnings, $level, []), $offset);
        $end = Utils\firstGreaterThan(Utils\access($this->levelEndings, $level, []), $offset);
        if ($types === self::ANY) {
            return Utils\allWithinRange($this->tokensByLevel[$level], $begin, $end);
        } else {
            $result = [];
            foreach ((array) $types as $type) {
                $candidates = Utils\access($this->tokensByLevelAndType, [$level, $type], []);
                $result = array_merge(Utils\allWithinRange($candidates, $begin, $end), $result);
            }
            return $result;
        }
    }

    function nextSibling($types, $offset)
    {
        $level = $this->levels[$offset];
        $end = Utils\firstGreaterThan(Utils\access($this->levelEndings, $level, []), $offset);
        if ($types === self::ANY) {
            return Utils\firstGreaterThan($this->tokensByLevel[$level], $offset);
        } else {
            $next = INF;
            foreach ((array) $types as $type) {
                $candidates = Utils\access($this->tokensByLevelAndType, [$level, $type], []);
                $next = min(Utils\firstGreaterThan($candidates, $offset), $next);
            }
            return ($next < $endpoint) ? $next : INF;
        }
    }

    function lastSibling($offset)
    {
        $level = $this->levels[$offset];
        if (!isset($this->levelEndings[$level])) {
            $this->levelEndings[$level] = [];
        }
        return Utils\firstGreaterThan($this->levelEndings[$level], $offset);
    }

    function next($types, $offset)
    {
        if (!is_array($types)) {
            $candidates = Utils\access($this->tokensByType, $types, []);
            return Utils\firstGreaterThan($candidates, $offset);
        }
        $result = INF;
        foreach ($types as $type) {
            $result = min($this->next($type, $offset), $result);
        }
        return $result;
    }

    function all($types)
    {
        if (!is_array($types)) {
            return Utils\access($this->tokensByType, $types, []);
        }
        $result = [];
        foreach ($types as $type) {
            $result = array_merge($result, $this->all($type));
        }
        sort($result);
        return $result;
    }

    function match($offset)
    {
        return isset($this->matchingBrackets[$offset]) ? $this->matchingBrackets[$offset] : INF;
    }

    function splice($splice, $offset, $length = 0, $policy = self::OVERWRITE)
    {
        if ($policy === self::OVERWRITE) {
            $this->splices[$offset] = $splice;
        } elseif ($policy === self::PREPEND || $policy === self::APPEND) {
            if (!isset($this->splices[$offset])) {
                $this->splices[$offset] = '';
            }
            if ($policy === self::PREPEND) {
                $this->splices[$offset] = $splice . $this->splices[$offset];
            } elseif ($policy === self::APPEND) {
                $this->splices[$offset] .= $splice;
            }
        }
        if (!isset($this->spliceLengths[$offset])) {
            $this->spliceLengths[$offset] = 0;
        }
        $this->spliceLengths[$offset] = max($length, $this->spliceLengths[$offset]);
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

    static function junk()
    {
        return [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];
    }

    function __toString()
    {
        if ($this->code === null) {
            $this->createCodeFromTokens();
        }
        return (string) $this->code;
    }

    function flush()
    {
        $this->initialize(token_get_all($this));
    }
}
