<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Admin\Entities\Admin\LoginController;

class AdminUsersServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('adminUsers',function ($app){
            return new LoginController();
        });
    }
}

