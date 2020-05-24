<?php

namespace App\Facades\Helper\Facade;

use Illuminate\Support\Facades\Facade;

class Helper extends Facade
{
    public static function getFacadeAccessor()
    {
        return 'helper';
    }
}
