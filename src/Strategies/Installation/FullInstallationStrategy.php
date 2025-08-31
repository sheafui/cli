<?php


namespace Sheaf\Cli\Strategies\Installation;

use Sheaf\Cli\Contracts\BaseInstallationStrategy;
use Sheaf\Cli\Traits\CanHandleFilesInstallation;
use Sheaf\Cli\Services\SheafConfig;
use Sheaf\Cli\Traits\CanHandleDependenciesInstallation;
use Illuminate\Console\Command;

class FullInstallationStrategy extends BaseInstallationStrategy
{

    use CanHandleDependenciesInstallation;
    use CanHandleFilesInstallation;

    public function execute($componentResources): int
    {

        $createdFiles = $this->installFiles($componentResources->get('files'));

        SheafConfig::saveInstalledComponent($this->componentName);

        $this->reportInstallation($createdFiles);

        $this->runInitialization();

        $this->installDependencies($componentResources->get('dependencies'));

        return Command::SUCCESS;
    }

    public function runInitialization()
    {
        $this->initCommand($this->command);
        $this->initConsoleComponent($this->consoleComponent);
        $this->initInstallationConfig($this->installationConfig);
        $this->initInstallationConfigForFilesInstallation($this->installationConfig);
    }

    private function reportInstallation(array $createdFiles): void
    {
        $this->reportSuccess();

        foreach ($createdFiles as $file) {
            $this->command->info(" <fg=white>{$file['path']} has been</fg=white> <bg=green;fg=black> {$file['action']} </bg=green;fg=black>\n");
        }
    }

}
