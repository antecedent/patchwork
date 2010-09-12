<?php

namespace Patchwork;

const LISTENERS = 'Patchwork\LISTENERS';
const DISPATCH_STACK = 'Patchwork\DISPATCH_STACK';

function dispatch($function, &$result)
{
    if (!isset($GLOBALS[LISTENERS][$function])) {
        return false;
    }
    try {
        $result = call_user_func($GLOBALS[LISTENERS][$function]);
        return true;
    } catch (ListenerSkippedException $e) {
        return false;
    }
}

function listen($subject, $listener)
{
    if (isset($GLOBALS[LISTENERS][$subject])) {
        throw new \LogicException("A listener for $subject already exists");
    }
    $GLOBALS[LISTENERS][$subject] = $listener;
}

function dismiss($subject)
{
    unset($GLOBALS[LISTENERS][$subject]);
}

function resume()
{
    throw new ListenerSkippedException;
}

function resume_if($condition)
{
    if ($condition) {
        resume();
    }
}

function resume_unless($condition)
{
    resume_if(!$condition);
}

class ListenerSkippedException extends \Exception
{
}

function include_patched($file, $origin = null, $require = false, $once = false)
{
    $file = locate_file($file, $origin);
    static $included = array();
    if (isset($included[$file]) && $once) {
        return true;
    }
    $source = file_get_contents($file, true);
    if ($source === false) {
        return $require ? (require $file) : (include $file);
    }
    $included[$file] = true;
    $result = eval_patched($source, $file, false);
    return $once ? true : $result;
}

function locate_file($file, $origin)
{
    $file_in_origin_dir = dirname($origin) . DIRECTORY_SEPARATOR . $file;
    if (file_exists($file_in_origin_dir)) {
        return $file_in_origin_dir;
    }
    return realpath($file);
}

function include_patched_once($file, $origin = null)
{
    return include_patched($file, $origin, false, true);
}

function require_patched($file, $origin = null)
{
    return include_patched($file, $origin, true, false);
}

function require_patched_once($file, $origin = null)
{
    return include_patched($file, $origin, true, true);
}

function eval_patched($code, $file = null, $in_php_mode = true)
{ 
    $source = new Source($in_php_mode ? php_to_tokens($code) : token_get_all($code), $file);
    patch($source);
    $code = source_to_string($source);
    return eval($in_php_mode ? $code : ("?". ">" . $code));
}

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
    var $file;
    
    function __construct($tokens, $file = null)
    {
        $this->tokens = $tokens;
        $this->file = $file;
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

function patch(Source $s)
{
    patch_functions($s);
    patch_includes($s);
    expand_magic_constants($s);
}

function patch_functions(Source $s)
{
    foreach (find_all(T_FUNCTION, $s) as $function) {
        $bracket   = find_next(LEFT_CURLY_BRACKET, $function, $s);
        $semicolon = find_next(SEMICOLON, $function, $s);
        # Make sure there is a function body
        if ($bracket < $semicolon) {
            splice('if (\Patchwork\dispatch(__METHOD__, $result)) return $result;', $bracket + 1, 0, $s);
        }
    }
}

function patch_includes(Source $s)
{
    foreach(array(T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_EVAL) as $type) {
        foreach (find_all($type, $s) as $include) {
            $semicolon = find_next(SEMICOLON, $include, $s);
            $replacement = '\Patchwork\\' . $s->tokens[$include][STRING_OFFSET] . '_patched(';
            splice($replacement, $include, 1, $s);
            splice(', ' . var_export($s->file, true) . ')', $semicolon, 0, $s);
        }
    }
}

function expand_magic_constants(Source $s)
{
    if ($s->file !== null) {
        expand_constants(T_FILE, $s->file, $s);
        expand_constants(T_DIR, dirname($s->file), $s);
    }    
}

function expand_constants($search, $replacement, Source $s)
{
    foreach (find_all($search, $s) as $constant) {
        splice(var_export($replacement, true), $constant, 1, $s);
    }
}

class Stream
{
    
}
