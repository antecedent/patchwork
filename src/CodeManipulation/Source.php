<?php

/**
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2016 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CodeManipulation;

use Patchwork\Utils;

class Source
{
    const TYPE_OFFSET = 0;
    const STRING_OFFSET = 1;

    const ANY = null;

    public $tokens;
    public $tokensByType;
    public $splices;
    public $spliceLengths;
    public $code;
    public $file;
    public $matchingBrackets;
    public $levels;
    public $levelEndpoints;
    public $tokensByLevel;
    public $tokensByLevelAndType;

    function __construct($tokens)
    {
        $this->initialize(is_array($tokens) ? $tokens : token_get_all($tokens));
    }

    function initialize(array $tokens)
    {
        $this->tokens = $tokens;
        $this->tokens[] = [T_WHITESPACE, ""];
        $this->indexTokensByType();
        $this->collectBracketMatchings();
        $this->collectLevelInfo();
        $this->splices = $this->spliceLengths = [];
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
        $this->levelEndpoints = [];
        $this->tokensByLevelAndType = [];
        foreach ($this->tokens as $offset => $token) {
            $type = $token[self::TYPE_OFFSET];
            switch ($type) {
                case '(':
                case '[':
                case '{':
                case T_CURLY_OPEN:
                case T_DOLLAR_OPEN_CURLY_BRACES:
                case T_OPEN_TAG:
                case T_OPEN_TAG_WITH_ECHO:
                    $level++;
                    break;
                case ')':
                case ']':
                case '}':
                case T_CLOSE_TAG:
                    $level--;
                    Utils\appendUnder($this->levelEndpoints, $level, $offset);
            }
            $this->levels[$offset] = $level;
            Utils\appendUnder($this->tokensByLevel, $level, $offset);
            Utils\appendUnder($this->tokensByLevelAndType, [$level, $type], $offset);
        }
    }

    function nextOnLevel($types, $offset)
    {
        $level = $this->levels[$offset];
        if (!isset($this->levelEndpoints[$level])) {
            $this->levelEndpoints[$level] = [];
        }
        $endpoint = Utils\findFirstGreaterThan($this->levelEndpoints[$level], $offset);
        if ($types === self::ANY) {
            return Utils\findFirstGreaterThan($this->tokensByLevel[$level], $offset);
        } else {
            $next = INF;
            foreach ((array) $types as $type) {
                $candidates = Utils\access($this->tokensByLevelAndType, [$level, $type], []);
                $next = min(Utils\findFirstGreaterThan($candidates, $offset), $next);
            }
            return ($next < $endpoint) ? $next : INF;
        }
    }

    function next($types, $offset)
    {
        if (!is_array($types)) {
            $candidates = Utils\access($this->tokensByType, $types, []);
            return Utils\findFirstGreaterThan($candidates, $offset);
        }
        $result = INF;
        foreach ($types as $type) {
            $result = min($this->next($type, $offset));
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

    function insert($snippet, $offset)
    {
        $this->splice($snippet, $offset, 0);
    }

    function delete($offset, $length = 1)
    {
        $this->splice('', $offset, $length);
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
        return (string) $this->code;
    }

    function flush()
    {
        $this->initialize(token_get_all($this));
    }
}
