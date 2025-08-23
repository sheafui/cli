<?php


namespace Fluxtor\Cli\Strategies\Installation;

use Fluxtor\Cli\Contracts\InstallationStrategyInterface;
use Fluxtor\Cli\Services\DependencyInstaller;
use Fluxtor\Cli\Services\FluxtorFileInstaller;
use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Component;

class InstallationStrategyFactory
{
    public static function create(
        InstallationConfig $config,
        Command $command,
        Component $consoleComponents,
        string $componentName
    ): InstallationStrategyInterface {

        if ($config->isDryRun()) {
            return new DryRunStrategy($command, $config, $componentName, $consoleComponents);
        }

        if ($config->shouldInstallOnlyDeps()) {
            return new DependencyOnlyStrategy($command, $config, $componentName, $consoleComponents);
        }

        if($config->shouldSkipInstallDeps()) {
            return new SkipDependenciesStrategy($command, $config, $componentName, $consoleComponents);
        }

        return new FullInstallationStrategy($command, $config, $componentName, $consoleComponents);
    }
}
