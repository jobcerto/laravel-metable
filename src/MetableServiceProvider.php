<?php

namespace Jobcerto\Metable;

use Illuminate\Support\ServiceProvider;

class MetableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Jobcerto\Pipeable\Commands\MakePipeCommand::class,
            ]);
        }
    }
}
