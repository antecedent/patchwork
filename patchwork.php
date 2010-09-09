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

class ListenerSkippedException extends \Exception
{
}

function include_patched($file, $require = false, $once = false)
{
	static $included = array();
	if (isset($included[$file]) && $once) {
		return true;
	}
	$source = file_get_contents($file);
	if ($source === false) {
		return $require ? (require $file) : (include $file);
	}
	$included[$file] = true;
	$result = eval_patched($source, false);
	return $once ? true : $result;
}

function include_once_patched($file)
{
	return include_patched($file, false, true);
}

function require_patched($file)
{
	return include_patched($file, true, false);
}

function require_once_patched($file)
{
	# TODO
	return include_patched($file, true, true);
}

function eval_patched($source, $inPhpMode = true)
{
	if (!$inPhpMode) {
		$source = "?>" . $source;
	}
	$tokens = patch(token_get_all($source));
	$source = tokens_to_string($tokens);
	return eval($source);
}

function patch(array $tokens) 
{
	$tokens = inject_intercepting_code($tokens);
	$tokens = inject_propagating_code($tokens);
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
		$tokens = inject_intercepting_code_segment($pos, $tokens);
	}
	return $tokens;
}

function inject_intercepting_code_segment($pos, array $tokens)
{
	find_next(LEFT_CURLY_BRACKET, $pos, $tokens);
	return insert(get_intercepting_segment_tokens(), $pos + 1, $tokens);
}

function inject_propagating_code(array $tokens)
{
	foreach (get_propagating_replacements() as $search => $replacement) {
		$pos = BEFORE_FIRST;
		while (find_next($search, $pos, $tokens)) {
			$tokens[$pos] = string_to_tokens($replacement);
			$tokens = insert(string_to_tokens("("), $pos + 1);
			$tokens = insert(string_to_tokens(")"), find_end_of_statement($pos, $tokens));
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

function find_end_of_statement($pos, array $tokens)
{
	# FIXME: Fails when lambdas occur in the current statement
	find_next(SEMICOLON, $pos, $tokens);
	return $pos;
}

function insert(array $insertion, $pos, array $tokens)
{
	return array_merge(array_slice($tokens, 0, $pos), $insertion, array_slice($tokens, $pos));
}

function get_token_type($token)
{
	if (is_array($token)) {
		return reset($token);
	}
	if (is_string($token)) {
		return $token;
	}
}

function find_next($types, &$pos, array $tokens)
{
	$types = (array) $types;
	while (++$pos < count($tokens)) {
		if (in_array(get_token_type($tokens[$pos]), $types)) {
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

function token_to_string($token)
{
	if (is_array($token)) {
		list($type, $string) = $token;
		return $string;
	}
	if (is_string($token)) {
		return $token;
	}
}

function tokens_to_string(array $tokens)
{
	$string = "";
	foreach ($tokens as $token) {
		$string .= token_to_string($token);
	}
	return $string;
}

function php_to_tokens($code)
{
	$tokens = token_get_all("<?php " . $code);
	return array_slice($tokens, 1);
}
