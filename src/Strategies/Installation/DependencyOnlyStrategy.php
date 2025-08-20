<?php


namespace Fluxtor\Cli\Strategies\Installation;

use Fluxtor\Cli\Contracts\BaseInstallationStrategy;
use Illuminate\Console\Command;

class DependencyOnlyStrategy extends BaseInstallationStrategy
{

    public function execute($componentResources): int
    {
        $name = $this->installationConfig->componentHeadlineName();

        $dependencies = $componentResources->get('dependencies');

        if (!$dependencies) {
            $this->command->info("<fg=white></fg=white> <bg=green;fg=black> $name </bg=green;fg=black> has no dependencies to be installed.");
            
            return Command::SUCCESS;
        }
        $this->command->info(" <fg=white>Installing Only Dependencies of</fg=white> <bg=green;fg=black> $name </bg=green;fg=black>");

        $this->dependencyInstaller->install($dependencies);

        return Command::SUCCESS;
    }
}
