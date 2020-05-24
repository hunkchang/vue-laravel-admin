<?php

namespace Rovaychang\Admin;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function boot(){
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'admin');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'admin');
        $this->loadRoutesFrom(__DIR__ . '/../routes/route.php');
        if (file_exists($routes = admin_path('routes.php'))) {
            $this->loadRoutesFrom($routes);
        }

        $this->registerPublishing();
    }


    protected function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config' => config_path()], 'laravel-vue-admin-config');
            $this->publishes([__DIR__ . '/../resources/lang' => resource_path('lang')], 'laravel-vue-admin-lang');
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'laravel-admin-migrations');
            $this->publishes([__DIR__ . '/../public' => public_path('vendor/laravel-vue-admin')], 'laravel-vue-admin-assets');
        }
    }

    public function register()
    {
        $this->app->singleton('admin',function (){
            return new Admin;
        });
    }
}
