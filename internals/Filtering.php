<?php

namespace Patchwork\Filtering;

use Patchwork\Utils;
use Patchwork\Call;
use Patchwork\Exceptions;

const FILTERS = 'Patchwork\Filtering\FILTERS';

const HANDLE_REFERENCE_OFFSET = 2;

function register($class, $method, $filter)
{
    $filters = &filtersFor($class, $method);
    $offset = Utils\append($filters, $filter);
    return array($class, $method, &$filters[$offset]);
}

function unregister($filterHandle)
{
	try {
		$filterHandle[HANDLE_REFERENCE_OFFSET] = null;
	} catch (\Exception $e) {
		$exception = $e;
	}
    list($class, $method) = $filterHandle;
    cleanUp($class, $method);
	if (isset($exception)) {
		throw $exception;
	}
}

function dispatch(Call $call)
{
    $filters = &filtersFor($call->class, $call->function);
    if (isset($filters)) {
        foreach ($filters as $filter) {
            $result = call_user_func($filter, $call);
            validateFilterResult($result);
        }
    }
    return $call->isCompleted();
}

function cleanUp($class, $method)
{
    $filters = &filtersFor($class, $method);
    if (isset($filters)) {
        foreach ($filters as $offset => $filter) {
            if ($filter === null) {
                unset($filters[$offset]);
            }
        }
    }
}

function &filtersFor($class, $method)
{
    return $GLOBALS[FILTERS][$class][$method];
}

function validateFilterResult($result)
{
    if ($result !== null) {
        throw new Exceptions\IllegalFilterResult;
    }
}

$GLOBALS[FILTERS] = array();
