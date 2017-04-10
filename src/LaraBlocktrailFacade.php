<?php

namespace Blockavel\LaraBlocktrail;

use Illuminate\Support\Facades\Facade;

class LaraBlocktrailFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return 'lara-blocktrail';
    }
}