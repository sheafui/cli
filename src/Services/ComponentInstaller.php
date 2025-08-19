<?php

namespace Fluxtor\Cli\Services;

use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;

class ComponentInstaller
{
    protected ComponentHttpClient $componentHttpClient;
    protected FluxtorFileInstaller $fileInstaller;
    protected DependencyInstaller $dependencyInstaller;
    protected string $name = '';

    public function __construct(
        protected Command $command,
        protected $components,
        protected InstallationConfig $installationConfig
    ) {
        $this->componentHttpClient = new ComponentHttpClient();
        $this->fileInstaller = new FluxtorFileInstaller($this->installationConfig);
        $this->dependencyInstaller = new DependencyInstaller(
            installationConfig: $this->installationConfig,
            command: $this->command,
            components: $this->components
        );
    }

    public function install(string $componentName)
    {
        try {

            $this->name = $componentName;
            $existingChoice = $this->handleExistingComponent();

            if ($existingChoice === Command::INVALID) {
                $this->command->error(" Cancelled");
                return;
            }

            $componentResources = $this->componentHttpClient->fetchResources($componentName);

            if (!$componentResources['success']) {
                $this->components->error($componentResources['message']);
                return Command::FAILURE;
            }

            $this->command->newLine();

            if ($this->installationConfig->isDryRun()) {
                return $this->performDryRun($componentResources['data']['files'], $componentResources['data']['dependencies']);
            }

            if ($this->installationConfig->shouldInstallOnlyDeps()) {
                return $this->performOnlyDepsInstallation($componentResources['data']);
            }

            $this->performInstallation($componentResources['data']);
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());

            if (config('fluxtor.env') !== 'production') {
                $this->components->error($th->getTraceAsString());
            }
        }
    }

    public function confirmDestructiveAction()
    {
        return confirm("All the component files will be overwritten, you might lose your modifications. are you sure you want to processed?");
    }

    public function performInstallation(Collection $componentResources)
    {
        $createdFiles = $this->fileInstaller->install($componentResources->get('files'));

        FluxtorConfig::saveInstalledComponent($this->name);

        $this->reportInstallation($createdFiles);

        $this->dependencyInstaller->install($componentResources->get('dependencies'));

    }

    public function performOnlyDepsInstallation(Collection $componentResources)
    {
        $name = $this->installationConfig->componentHeadlineName();
        $dependencies = $componentResources->get('dependencies');

        if (!$dependencies) {
            $this->command->info("<fg=white></fg=white> <bg=green;fg=black> $name </bg=green;fg=black> has no dependencies to be installed.");
            return;
        }
        $this->command->info(" <fg=white>Installing Only Dependencies of</fg=white> <bg=green;fg=black> $name </bg=green;fg=black>");

        $this->dependencyInstaller->install($dependencies);
    }

    public function performDryRun(array $files, array $dependencies)
    {

        foreach ($files as $file) {
            $this->command->info("<fg=white>Will create: {$file['path']}</fg=white>");
        }

        $this->dependencyInstaller->install(Arr::wrap($dependencies['internal']));

        return Command::SUCCESS;
    }

    private function reportInstallation(array $createdFiles)
    {
        $name = $this->installationConfig->componentHeadlineName();

        $this->command->line(" <bg=green;fg=black> $name </bg=green;fg=black> <fg=white>has been installed successfully.</fg=white>");

        foreach ($createdFiles as $file) {
            $this->command->info(" <fg=white>{$file['path']} has been</fg=white> <bg=green;fg=black> {$file['action']} </bg=green;fg=black>\n");
        }
    }

    public function handleExistingComponent()
    {
        if (!$this->ensureComponentIsInstalled() || $this->installationConfig->shouldForceOverwriting()) {
            return;
        }

        $name = $this->installationConfig->componentHeadlineName(); // Assuming you have this method

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
        return File::exists(resource_path("views/components/ui/{$this->name}"));
    }
}
