<?php

namespace Modules\Admin\Entities\Admin\Facade;

use Illuminate\Support\Facades\Facade;

class AdminUsers extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'adminUsers';
    }
}
