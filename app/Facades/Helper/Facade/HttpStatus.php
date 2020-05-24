<?php

namespace App\Facades\Helper\Facade;

use Illuminate\Support\Facades\Facade;

class HttpStatus extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'http_status';
    }
}
