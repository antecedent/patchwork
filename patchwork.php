<?php

namespace Patchwork;

require __DIR__ . "/modules/dispatchment.php";
require __DIR__ . "/modules/parsing.php";
require __DIR__ . "/modules/source_patching.php";
require __DIR__ . "/modules/file_patching.php";
require __DIR__ . "/modules/stream_wrapper.php";

Stream::wrap();
