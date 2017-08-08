<?php

namespace Patchwork;

redefine('new PDO', always(new \stdClass));

assert(new \PDO instanceof \stdClass);
