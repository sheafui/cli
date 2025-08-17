<?php

namespace Fluxtor\Cli\Services;

use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

class ComponentInstaller
{
    protected ComponentHttpClient $componentHttpClient;

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
            $componentResources = $this->componentHttpClient->fetchResources($componentName);

            if (!$componentResources['success']) {
                $this->components->error($componentResources['message']);
                return Command::FAILURE;
            }

            if ($this->installationConfig->isDryRun()) {
                return $this->performDryRun($componentResources['files'], $componentResources['dependencies']);
            }

            $this->performInstallation($componentName, $componentResources['data']);
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());

            if (!app()->isProduction()) {
                $this->components->error($th->getTraceAsString());
            }
        }
    }

    public function performInstallation(string $componentName, Collection $componentResources)
    {
        $createdFiles = $this->installFiles($componentResources->get('files'));

        $this->saveInstalledComponent($componentName);

        $component = Str::of($componentName)->headline();

        $this->reportInstallation($component, $createdFiles);

        $this->installDeps($componentResources->get('dependencies'));
    }

    public function performDryRun(array $files, array $dependencies)
    {

        foreach ($files as $file) {
            $this->components->info("This File will be create: {$file['path']}");
        }

        if (!$dependencies) {
            return;
        }

        if ($internalDeps = Arr::wrap($dependencies['internal'])) {
            $this->command->info("This internal dependencies will be install: \n\n");
            foreach ($internalDeps as $dep) {
                $this->command->info("Dependency: $dep");
                $this->install($dep);
            }
        }

        return Command::SUCCESS;
    }

    private function reportInstallation(string $component, array $createdFiles)
    {
        $this->command->line("  <bg=green;fg=black> $component </bg=green;fg=black> has been installed successfully.");

        foreach ($createdFiles as $file) {
            $this->components->info($file['path'] . ' has been ' . $file['action']);
        }
    }

    private function createComponentFile(string $filePath, string $fileContent)
    {
        $directory = str($filePath)->beforeLast('/');
        File::ensureDirectoryExists($directory);
        File::replace($filePath, $fileContent);
    }


    private function installFiles($files)
    {
        $createdFiles = [];
        $forceFileCreation = $this->installationConfig->shouldForceOverwriting();

        foreach ($files as $file) {
            $filePath = $file['path'];
            $content = $file['content'];

            if (!file_exists($filePath)) {
                $this->createComponentFile($filePath, $content);
                $createdFiles[] = ['path' => $filePath, 'action' => 'created'];
                continue;
            }

            $shouldOverride = $forceFileCreation ? true : confirm($filePath . ' File already exists, do you want to override it?');

            if (!$shouldOverride) {
                $createdFiles[] = ['path' => $filePath, 'action' => 'skipped'];
                continue;
            }

            $this->createComponentFile($filePath, $content);

            $createdFiles[] = ['path' => $filePath, 'action' => 'overridden'];
        }

        return $createdFiles;
    }

    private function installDeps($dependencies)
    {
        if (!$dependencies) {
            return;
        }

        if (array_key_exists('internal', $dependencies) && $depInternal = Arr::wrap($dependencies['internal'])) {

            $this->installInternalDeps($depInternal);
        }

        if (array_key_exists('external', $dependencies) && $depExternal = Arr::wrap($dependencies['external'])) {

            $this->installExternalDeps($depExternal);
        }
    }

    public function installInternalDeps(array $deps)
    {
        $this->components->warn('This component has an external dependencies must be installed to work.');
        $installDependencies = $this->installationConfig->shouldInstallInternalDeps() ? true : confirm(label: 'This component need dependencies to work as expected, do you want to install them?', default: true);

        if (!$installDependencies) {
            return;
        }

        $this->components->info('↳ Installing internal dependencies');

        foreach ($deps as $dep => $info) {
            if ($this->shouldInstallDependency($dep, $info))
                $this->install($dep);
        }

        $this->components->info("All Dependencies installed and up to date.");
    }

    public function installExternalDeps(array $deps)
    {
        $this->components->warn('This component has an external dependencies must be installed to work.');
        $confirmInstall = $this->installationConfig->shouldInstallExternalDeps() ? true : confirm(label: 'This component need an external dependencies to work, do you want to install them?', default: true);

        if (!$confirmInstall) {
            return;
        }

        $this->components->info('↳ Installing external dependencies');
        foreach ($deps as $key => $dep) {
            $this->components->info("Installing $key...");
            Process::run($dep[1]);
        }
    }

    public function shouldInstallDependency($dependency, $info)
    {
        $components = $this->getInstalledComponents();

        if (!array_key_exists($dependency, $components['components'])) {
            return true;
        }

        return $components['components'][$dependency]['installationTime'] < $info['lastModified'];
    }

    public function saveInstalledComponent(string $componentName)
    {
        $components = $this->getInstalledComponents();

        $components['components'][$componentName] = [
            'installationTime' => time()
        ];

        File::put(base_path('fluxtor.json'), json_encode($components, true));
    }

    public function getInstalledComponents()
    {
        $components = [];

        if (File::exists(base_path('fluxtor.json'))) {
            $components = json_decode(File::get(base_path('fluxtor.json')), true);
        }

        return $components;
    }
}
