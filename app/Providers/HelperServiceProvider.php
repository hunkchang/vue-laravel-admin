<?php

namespace App\Providers;

use App\Facades\Helper\Helper;
use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('helper',function ($app){
            return new Helper();
        });
    }
}

