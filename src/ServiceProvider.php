<?php

namespace Fluxtor\Cli;

use Fluxtor\Cli\Commands\FluxtorInitCommand;
use Fluxtor\Cli\Commands\InstallComponentCommand;
use Fluxtor\Cli\Commands\ListCommand;
use Fluxtor\Cli\Commands\LoginCommand;
use Fluxtor\Cli\Commands\LogoutCommand;
use Fluxtor\Cli\Commands\WhoAmICommand;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;

class ServiceProvider extends SupportServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([FluxtorInitCommand::class]);
            $this->commands([LoginCommand::class]);
            $this->commands([ListCommand::class]);
            $this->commands([InstallComponentCommand::class]);
            $this->commands([LogoutCommand::class]);
            $this->commands([WhoAmICommand::class]);
        }

        $this->publishes(
            [
                __DIR__ . '/../config/fluxtor.php' => config_path('fluxtor.php'),
            ],
            'fluxtor-config',
        );
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/fluxtor.php', 'fluxtor');
    }
}
