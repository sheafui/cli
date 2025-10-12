<?php


namespace Sheaf\Cli\Strategies\Installation;

use Sheaf\Cli\Contracts\BaseInstallationStrategy;
use Sheaf\Cli\Traits\CanHandleDependenciesInstallation;
use Illuminate\Console\Command;
use Sheaf\Cli\Traits\CanUpdateSheafLock;

class DependencyOnlyStrategy extends BaseInstallationStrategy
{
    use CanHandleDependenciesInstallation;
    use CanUpdateSheafLock;

    public function execute($componentResources): int
    {
        $this->runInitialization();

        $name = $this->installationConfig->componentHeadlineName();

        $dependencies = $componentResources->get('dependencies');

        if (!$dependencies) {
            $this->command->info("<fg=white></fg=white> <bg=green;fg=black> $name </bg=green;fg=black> has no dependencies to be installed.");

            return Command::SUCCESS;
        }
        $this->command->info(" <fg=white>Installing Only Dependencies of</fg=white> <bg=green;fg=black> $name </bg=green;fg=black>");

        $this->installDependencies($dependencies);

        $this->updateSheafLock(null, $dependencies, $this->componentName);


        return Command::SUCCESS;
    }

    public function runInitialization()
    {
        $this->initCommand($this->command);
        $this->initConsoleComponent($this->consoleComponent);
        $this->initInstallationConfig($this->installationConfig);
    }
}
