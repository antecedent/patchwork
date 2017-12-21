<?php

namespace Patchwork;

redefine('define', always(null));

define('AN_UNLIKELY_CONSTANT', 123);

assert(!defined('AN_UNLIKELY_CONSTANT'));
