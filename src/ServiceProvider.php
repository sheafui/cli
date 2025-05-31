<?php

namespace Fluxtor\Cli;

use Fluxtor\Cli\Commands\ListCommand;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListCommand::class,  
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/fluxtor-cli.php' => config_path('fluxtor-cli.php')
        ], 'fluxtor-cli-config');
    }

    public function register()
    {
        // bind services if needed
    }
}