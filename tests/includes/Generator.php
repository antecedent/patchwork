<?php

function xrange($low, $high, $step = 1)
{
	for ($i = $low; $i <= $high; $i += $step) {
		yield $i;
	}
}
