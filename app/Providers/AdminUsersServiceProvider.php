<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Entities\Admin\AdminUsers;

class AdminUsersServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('adminUsers',function ($app){
            return new AdminUsers();
        });
    }
}

