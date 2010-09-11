<?php

/**
 * @author Ignas Rudaitis
 * @todo   this doc block
 */
namespace Patchwork;

# TODO FREE LISTENING (polymorphism?!), resume_if, resume_unless, dismissal handles, 
#      semantic patcher function names, doc comment, stack inspection

# Optimize with TokenCollection (or equivalent)

##
## Call dispatchment
## 


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

class ListenerSkippedException extends \Exception
{
}

##
## Patched code inclusion
##

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
	return $file;
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

function eval_patched($source, $file = null, $in_php_mode = true)
{
// DEBUG: echo "<hr><h1>$file</h1>";	
	$tokens = patch($in_php_mode ? php_to_tokens($source) : token_get_all($source), $file);
	$source = tokens_to_string($tokens);
// DEBUG: highlight_string($source);
	if (!$in_php_mode) {
		$source = "?>" . $source;
	}
	return eval($source);
}

##
## Code patching
##

function patch(array $tokens, $file = null) 
{
	$tokens = inject_intercepting_code($tokens);
	$tokens = inject_propagating_code($tokens, $file);
	$tokens = expand_magic_constants($tokens, $file);
	return $tokens;
}

const BEFORE_FIRST = -1;

const SEMICOLON = ";";

const LEFT_CURLY_BRACKET  = "{";
const RIGHT_CURLY_BRACKET = "}";

const LEFT_SQUARE_BRACKET  = "[";
const RIGHT_SQUARE_BRACKET = "]";

const LEFT_PARENTHESIS  = "(";
const RIGHT_PARENTHESIS = ")";

function inject_intercepting_code(array $tokens)
{
	$pos = BEFORE_FIRST;
	while (find_next(T_FUNCTION, $pos, $tokens)) {
		$bracket = $semicolon = $pos;
		find_next(SEMICOLON, $semicolon, $tokens);
		if (find_next(LEFT_CURLY_BRACKET, $bracket, $tokens) && $semicolon > $bracket) {
			array_splice($tokens, $bracket + 1, 0, get_intercepting_segment_tokens());
		}
	}
	return $tokens;
}

function inject_propagating_code(array $tokens, $file = null)
{
	foreach (get_propagating_replacements() as $search => $replacement) {
		$pos = BEFORE_FIRST;
		while (find_next($search, $pos, $tokens)) {
			array_splice($tokens, $pos, 1, php_to_tokens($replacement . "("));
			$end = ", " . var_export($file, true) . ")";
			array_splice($tokens, find_end_of_statement($pos, $tokens), 0, php_to_tokens($end));
		}
	}
	return $tokens;
}

function get_propagating_replacements()
{
	return array(
		T_INCLUDE      => '\Patchwork\include_patched',
		T_INCLUDE_ONCE => '\Patchwork\include_once_patched',
		T_REQUIRE      => '\Patchwork\require_patched',
		T_REQUIRE_ONCE => '\Patchwork\require_once_patched',
		T_EVAL         => '\Patchwork\eval_patched',
	);
}

# TODO refactor (function `replace`)
function expand_magic_constants(array $tokens, $file)
{
	if ($file === null) {
		return $tokens;
	}
	$pos = BEFORE_FIRST;
	while (find_next(T_FILE, $pos, $tokens)) {
		array_splice($tokens, $pos, 1, php_to_tokens(var_export($file, true)));
	}
	$pos = BEFORE_FIRST;
	while (find_next(T_DIR, $pos, $tokens)) {
		array_splice($tokens, $pos, 1, php_to_tokens(var_export(dirname($file), true)));
	}
	return $tokens;
}

function find_end_of_statement($pos, array $tokens)
{
	# FIXME: Fails when lambdas occur in the current statement
	find_next(SEMICOLON, $pos, $tokens);
	return $pos;
}

function find_next($type, &$pos, array $tokens)
{
	$count = count($tokens);
	while (++$pos < $count) {
		if ($tokens[$pos][0] == $type) {
			return true;
		}
	}
	return false;
}

function get_intercepting_segment_tokens()
{
	return php_to_tokens('
		if (\Patchwork\dispatch(__METHOD__, $result)) {
			return $result;
		}
	');
}

function tokens_to_string(array $tokens)
{
	$string = '';
	foreach ($tokens as $token) {
		$string .= isset($token[1]) ? $token[1] : $token;
	}
	return $string;
}

function php_to_tokens($code)
{
	$tokens = token_get_all("<?php " . $code);
	return array_slice($tokens, 1);
}
