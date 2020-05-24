<?php

namespace App\Providers;

use App\Facades\Helper\HttpStatus;
use Illuminate\Support\ServiceProvider;

class HttpStatusServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('http_status',function ($app){
            return new HttpStatus();
        });
    }
}

