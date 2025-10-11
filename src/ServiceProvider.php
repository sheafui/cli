<?php

namespace Sheaf\Cli;

use Sheaf\Cli\Commands\SheafInitCommand;
use Sheaf\Cli\Commands\InstallComponentCommand;
use Sheaf\Cli\Commands\ListCommand;
use Sheaf\Cli\Commands\LoginCommand;
use Sheaf\Cli\Commands\LogoutCommand;
use Sheaf\Cli\Commands\WhoAmICommand;
use Illuminate\Support\ServiceProvider as SupportServiceProvider;
use Sheaf\Cli\Commands\RemoveComponentCommand;
use Sheaf\Cli\Commands\UpdateComponentCommand;

class ServiceProvider extends SupportServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SheafInitCommand::class]);
            $this->commands([LoginCommand::class]);
            $this->commands([ListCommand::class]);
            $this->commands([InstallComponentCommand::class]);
            $this->commands([LogoutCommand::class]);
            $this->commands([WhoAmICommand::class]);
            $this->commands([UpdateComponentCommand::class]);
            $this->commands([RemoveComponentCommand::class]);
        }

        $this->publishes(
            [
                __DIR__ . '/../config/sheaf.php' => config_path('sheaf.php'),
            ],
            'sheaf-config',
        );
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sheaf.php', 'sheaf');
    }
}
