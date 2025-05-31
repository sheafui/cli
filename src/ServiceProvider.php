<?php

namespace Fluxtor\Cli;

use Fluxtor\Cli\Commands\ListCommand;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider{
    public function boot()
    {
        // Register commands only if running in console (Artisan)
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListCommand::class,  // List all your commands here
            ]);
        }
    }

    public function register()
    {
        // You can bind services here if needed
    }
}