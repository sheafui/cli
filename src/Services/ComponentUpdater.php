<?php

namespace Sheaf\Cli\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Sheaf\Cli\Support\InstallationConfig;
use Sheaf\Cli\Traits\CanHandleDependenciesInstallation;

use function Laravel\Prompts\confirm;

class ComponentUpdater
{
    use CanHandleDependenciesInstallation;

    protected ComponentHttpClient $httpClient;
    public function __construct(protected string $component, protected $consoleOutput, protected Command $artisanCommand)
    {
        $this->httpClient = new ComponentHttpClient();
    }



    public function handleUpdate()
    {

        if (!$this->isComponentInstalled()) {
            return $this->installComponent();
        }

        $resources = $this->httpClient->fetchResources($this->component)['data'];

        if (!$this->needsUpdate($resources['lastModified'])) {
            $this->consoleOutput->info("✔ {$this->component} is already up to date.");
            return Command::SUCCESS;
        }

        $confirmUpdate = confirm("↻ An update is available. Do you want to overwrite the existing component files?");

        if (!$confirmUpdate) {
            $this->artisanCommand->info("<fg=red>✖ Update canceled.</fg=red>");
            return Command::SUCCESS;
        }

        $this->replaceFiles($resources);
        $this->updateDependencies($resources);

        SheafConfig::saveInstalledComponent($this->component);
    }

    /**
     * Update the component files with the new resources.
     *
     * @param array $resources
     * @return void
     */
    public function replaceFiles(Collection $resources)
    {
        foreach ($resources['files'] as $file) {
            $path = $file['path'];
            $content = $file['content'];
            $this->updateComponentFile($path, $content);
        }

        $this->artisanCommand->info("✔ {$this->component} files have been updated.");
    }

    public function updateDependencies($resources)
    {
        $dependencies = $resources->get("dependencies");

        if (!$dependencies) {
            $this->artisanCommand->info(" {$this->component} has no dependencies.");

            return Command::SUCCESS;
        }

        $this->artisanCommand->info("↻ Installing dependencies for {$this->component}...");

        $this->initialize();

        $this->installDependencies($dependencies);
    }


    public function isComponentInstalled()
    {
        $path = resource_path("views/components/ui/{$this->component}");

        return File::isDirectory($path);
    }

    public function installComponent()
    {
        $confirm = confirm("{$this->component} is not installed. Would you like to install it now?");

        if (!$confirm) {
            return Command::FAILURE;
        }

        $installationConfig = new InstallationConfig($this->component);

        (new ComponentInstaller($this->artisanCommand, $this->consoleOutput, $installationConfig))->install($this->component);
        
        return Command::SUCCESS;
    }

    public function needsUpdate($lastModified)
    {
        $sheafFile = SheafConfig::getSheafFile();

        if (!$sheafFile) {
            return true;
        }

        if (!array_key_exists($this->component, $sheafFile['components'])) {
            return true;
        }

        return $sheafFile['components'][$this->component]['installationTime'] < $lastModified;
    }


    private function updateComponentFile(string $filePath, string $fileContent)
    {
        $directory = str($filePath)->beforeLast('/');
        File::ensureDirectoryExists($directory);
        File::put($filePath, $fileContent);
    }

    public function initialize()
    {
        $installationConfig = new InstallationConfig($this->component);

        $this->initCommand($this->artisanCommand);

        $this->initConsoleComponent($this->consoleOutput);

        $this->initInstallationConfig($installationConfig);
    }
}
