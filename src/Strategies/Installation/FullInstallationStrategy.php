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

    private function reportInstallation(array $createdFiles): void
    {
        $this->reportSuccess();

        foreach ($createdFiles as $file) {
            $this->command->info(" <fg=white>{$file['path']} has been</fg=white> <bg=green;fg=black> {$file['action']} </bg=green;fg=black>\n");
        }
    }

    private function updateSheafLock($createdFiles, $dependencies)
    {
        $sheafLockPath = base_path("sheaf-lock.json");

        $sheafLock = [];

        if (File::exists($sheafLockPath)) {
            $sheafLock = json_decode(File::get($sheafLockPath), true) ?: [];
        }

        
        $sheafLock['files'] ??= [];
        
        foreach ($createdFiles as $file) {
            if(str_contains($file['path'], "resources/views/components/ui/{$this->componentName}")) continue;

            $sheafLock['files'][$file['path']] ??= [];
            if (!in_array($this->componentName, $sheafLock['files'][$file['path']], true)) {
                $sheafLock['files'][$file['path']][] = $this->componentName;
            }
        }
        
        if ($dependencies && array_key_exists('helpers', $dependencies)) {
            $sheafLock['helpers'] ??= [];

            foreach ($dependencies['helpers'] as $helper => $value) {
                $sheafLock['helpers'][$helper] ??= [];

                if (!in_array($this->componentName, $sheafLock['helpers'][$helper], true)) {
                    $sheafLock['helpers'][$helper][] = $this->componentName;
                }
            }
        }


        if ($dependencies && array_key_exists('internal', $dependencies) && $depInternal = Arr::wrap($dependencies['internal'])) {
            $sheafLock['internalDependencies'] ??= [];
    
            foreach ($depInternal as $dep => $value) {
                $sheafLock['internalDependencies'][$dep] ??= [];

                if (!in_array($this->componentName, $sheafLock['internalDependencies'][$dep], true)) {
                    $sheafLock['internalDependencies'][$dep][] = $this->componentName;
                }
            }
        }


        File::put($sheafLockPath, json_encode($sheafLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
