<?php

namespace Sheaf\Cli\Services;

use Sheaf\Cli\Strategies\Installation\InstallationStrategyFactory;
use Sheaf\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ComponentInstaller
{
    protected ComponentHttpClient $componentHttpClient;
    protected string $name = '';

    public function __construct(
        protected Command $command,
        protected $components,
        protected InstallationConfig $installationConfig
    ) {
        $this->componentHttpClient = new ComponentHttpClient();
    }

    public function install(string $componentName)
    {
        try {

            $this->name = $componentName;

            $componentResources = $this->componentHttpClient->fetchResources($componentName);

            $existingChoice = $this->handleExistingComponent();

            if ($existingChoice === Command::INVALID) {
                $this->command->error(" Cancelled");
                return Command::INVALID;
            }

            $strategy = InstallationStrategyFactory::create(
                $this->installationConfig,
                $this->command,
                $this->components,
                $componentName
            );

            return $strategy->execute(collect($componentResources['data']));
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());

            if (config('sheaf.env') !== 'production') {
                $this->components->error($th->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    public function handleExistingComponent()
    {
        if (!$this->installationConfig->allOptionsFalse() || !$this->ensureComponentIsInstalled() || $this->installationConfig->shouldForceOverwriting()) {
            return null;
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

    public function ensureComponentIsInstalled()
    {
        return File::exists(resource_path("views/components/ui/{$this->installationConfig->componentName()}"));
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

    public function confirmDestructiveAction()
    {
        return confirm("All the component files will be overwritten, you might lose your modifications. are you sure you want to processed?");
    }
}
