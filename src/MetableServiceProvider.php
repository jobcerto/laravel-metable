<?php

namespace Jobcerto\Metable;

use Illuminate\Support\ServiceProvider;

class MetableServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/database/migrations/');
        }

        if ( ! class_exists('CreateMetableTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__ . '/database/migrations/create_metable_table.php.stub' => database_path('migrations/' . $timestamp . '_create_metable_table.php'),
            ], 'migrations');
        }

        $this->publishes([
            __DIR__ . '/config/metable.php' => config_path('metable.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/metable.php', 'metable');
    }
}
