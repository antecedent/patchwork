<?php

class FooObject {
    public static function staticFunction() {
        return 'original';
    }
}

class BarObject {
    public function callFoo() {
        return call_user_func([FooObject::class,'staticFunction']);
    }
}
