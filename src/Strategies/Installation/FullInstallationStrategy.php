<?php


namespace Sheaf\Cli\Strategies\Installation;

use Sheaf\Cli\Contracts\BaseInstallationStrategy;
use Sheaf\Cli\Traits\CanHandleFilesInstallation;
use Sheaf\Cli\Services\SheafConfig;
use Sheaf\Cli\Traits\CanHandleDependenciesInstallation;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

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

        $this->updateSheafLock($createdFiles, $componentResources->get('dependencies'));
        return Command::SUCCESS;
    }

    public function runInitialization()
    {
        $this->initCommand($this->command);
        $this->initConsoleComponent($this->consoleComponent);
        $this->initInstallationConfig($this->installationConfig);
        $this->initInstallationConfigForFilesInstallation($this->installationConfig);
    }

    protected function reportInstallation(array $createdFiles): void
    {
        $this->reportSuccess();

        foreach ($createdFiles as $file) {
            $this->command->info(" <fg=white>{$file['path']} has been</fg=white> <bg=green;fg=black> {$file['action']} </bg=green;fg=black>\n");
        }
    }

    protected function updateSheafLock($files, $dependencies)
    {
        $sheafLock = SheafConfig::loadSheafLock();


        $this->updateFilesInLock($sheafLock, $files);
        $this->updateDependenciesInLock($sheafLock, $dependencies);

        SheafConfig::saveSheafLock($sheafLock);
    }


    protected function updateFilesInLock(&$sheafLock, $files)
    {
        $sheafLock['files'] ??= [];

        foreach ($files as $file) {
            if ($this->shouldSkipFile($file['path'])) continue;

            $this->addComponentToLockEntry($sheafLock['files'], $file['path']);
        }
    }

    protected function updateDependenciesInLock(&$sheafLock, $dependencies)
    {
        if (isset($dependencies['helpers'])) {
            $this->addComponentToLockSection($sheafLock, 'helpers', $dependencies['helpers']);
        }

        if (isset($dependencies['internal'])) {
            $this->addComponentToLockSection($sheafLock, 'internalDependencies', Arr::wrap($dependencies['internal']));
        }
    }

    protected function shouldSkipFile($path)
    {
        return str_contains($path, "resources/views/components/ui/{$this->componentName}");
    }

    protected function addComponentToLockSection(&$sheafLock, $section, $items)
    {
        $sheafLock[$section] ??= [];

        foreach ($items as $item => $value) {
            $this->addComponentToLockEntry($sheafLock[$section], $item);
        }
    }

    protected function addComponentToLockEntry(&$lockSection, $key)
    {
        $lockSection[$key] ??= [];
        if (!in_array($this->componentName, $lockSection[$key], true)) {
            $lockSection[$key][] = $this->componentName;
        }
    }
}
