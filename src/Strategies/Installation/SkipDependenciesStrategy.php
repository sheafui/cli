<?php


namespace Fluxtor\Cli\Strategies\Installation;

use Fluxtor\Cli\Contracts\BaseInstallationStrategy;
use Fluxtor\Cli\Services\FluxtorConfig;
use Fluxtor\Cli\Services\FluxtorFileInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class SkipDependenciesStrategy extends BaseInstallationStrategy
{


    public function execute($componentResources): int
    {

        $existingChoice = $this->handleExistingComponent();

            if ($existingChoice === Command::INVALID) {
                $this->command->error(" Cancelled");
                return Command::INVALID;
            }

        $createdFiles = $this->fileInstaller->install($componentResources->get('files'));

        FluxtorConfig::saveInstalledComponent($this->componentName);

        $this->reportInstallation($createdFiles);
        
        return Command::SUCCESS;
    }

    private function reportInstallation(array $createdFiles): void
    {
        $this->reportSuccess();

        foreach ($createdFiles as $file) {
            $this->command->info(" <fg=white>{$file['path']} has been</fg=white> <bg=green;fg=black> {$file['action']} </bg=green;fg=black>\n");
        }
    }

    public function confirmDestructiveAction()
    {
        return confirm("All the component files will be overwritten, you might lose your modifications. are you sure you want to processed?");
    }



    public function handleExistingComponent()
    {
        if (!$this->ensureComponentIsInstalled() || $this->installationConfig->shouldForceOverwriting()) {
            return;
        }

        $name = $this->installationConfig->componentHeadlineName();

        $choice = select(
            label: "Component '{$name}' already exists. What would you like to do?",
            options: [
                'interactive' => 'Prompt me for each file (recommended)',
                'dependencies' => 'Skip component files, only update dependencies',
                'overwrite' => 'Overwrite all files without asking (destructive)',
                'cancel' => 'Cancel installation'
            ],
            default: 'prompt'
        );

        return $this->processExistingComponentChoice($choice);
    }

    public function processExistingComponentChoice($choice)
    {
        return match ($choice) {
            'interactive' => $this->handleInteractiveChoice(),

            'dependencies' => $this->handleDependenciesChoice(),

            'overwrite' => $this->handleOverwriteChoice(),

            'cancel' => Command::INVALID,
        };
    }

    public function handleInteractiveChoice()
    {
        info('Will prompt you for each file during installation');
        return null;
    }


    public function handleDependenciesChoice()
    {
        $this->installationConfig->setOnlyDeps(true);
        $this->command->info('Skipping component files, checking dependencies...');
        return null;
    }


    public function handleOverwriteChoice()
    {
        if ($this->confirmDestructiveAction()) {
            $this->installationConfig->setOverwrite(true);
            $this->command->warn('All component files will be overwritten.');
            return null;
        } else {
            return Command::INVALID;
        }
    }

    public function ensureComponentIsInstalled()
    {
        return File::exists(resource_path("views/components/ui/{$this->installationConfig->componentName()}"));
    }
}
