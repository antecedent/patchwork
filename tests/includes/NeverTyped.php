<?php

class NeverTypedException extends Exception {
}

function iAmNeverTyped() : never
{
    throw new NeverTypedException( 'A never-typed function must exit or throw, so we throw.' );
}

function iAmNotNeverTyped()
{
}
