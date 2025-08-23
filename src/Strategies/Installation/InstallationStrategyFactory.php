<?php


namespace Fluxtor\Cli\Strategies\Installation;

use Fluxtor\Cli\Contracts\InstallationStrategyInterface;
use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;

class InstallationStrategyFactory
{
    public static function create(
        InstallationConfig $config,
        Command $command,
        $consoleComponents,
        string $componentName
    ): InstallationStrategyInterface {

        if ($config->isDryRun()) {
            return new DryRunStrategy($command, $config, $consoleComponents, $componentName);
        }

        if ($config->shouldInstallOnlyDeps()) {
            return new DependencyOnlyStrategy($command, $config, $consoleComponents, $componentName);
        }

        if($config->shouldSkipInstallDeps()) {
            return new SkipDependenciesStrategy($command, $config, $consoleComponents, $componentName);
        }

        return new FullInstallationStrategy($command, $config, $consoleComponents, $componentName);
    }
}
