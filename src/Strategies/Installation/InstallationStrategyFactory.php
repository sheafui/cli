<?php


namespace Fluxtor\Cli\Strategies\Installation;

use Fluxtor\Cli\Contracts\InstallationStrategyInterface;
use Fluxtor\Cli\Services\DependencyInstaller;
use Fluxtor\Cli\Services\FluxtorFileInstaller;
use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;

class InstallationStrategyFactory
{
    public static function create(
        InstallationConfig $config,
        Command $command,
        FluxtorFileInstaller $fileInstaller,
        DependencyInstaller $dependencyInstaller,
        string $componentName
    ): InstallationStrategyInterface {

        if ($config->isDryRun()) {
            return new DryRunStrategy($command, $config, $fileInstaller, $dependencyInstaller, $componentName);
        }

        if ($config->shouldInstallOnlyDeps()) {
            return new DependencyOnlyStrategy($command, $config, $fileInstaller, $dependencyInstaller, $componentName);
        }

        return new FullInstallationStrategy($command, $config, $fileInstaller, $dependencyInstaller, $componentName);
    }
}
