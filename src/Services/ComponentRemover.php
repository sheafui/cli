<?php


namespace Sheaf\Cli\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;

class ComponentRemover
{
    protected $output;
    protected $componentName;

    public function __construct(protected $command) {}

    public function remove($name)
    {
        $this->componentName = $name;


        $this->deleteComponentFiles();

        $this->cleaningSheafLock($name);
    }

    protected function deleteComponentFiles()
    {
        $componentDirectory = resource_path("views/components/ui/{$this->componentName}");

        if (File::isDirectory($componentDirectory)) {
            File::deleteDirectory($componentDirectory);
            $this->message("+ Deleted directory: resources/views/components/ui/{$this->componentName}");
        }

        $componentFile = resource_path("views/components/ui/{$this->componentName}.blade.php");

        if (File::exists($componentFile)) {
            File::delete($componentFile);
        }
    }

    protected function cleaningSheafLock()
    {
        $sheafLock = SheafConfig::loadSheafLock();

        $this->cleaningFiles($sheafLock);

        $this->cleaningDependencies($sheafLock);

        $this->cleaningHelpers($sheafLock);

        $this->updateSheafFile();

        SheafConfig::saveSheafLock($sheafLock);
    }

    protected function cleaningFiles(&$sheafLock)
    {
        foreach ($sheafLock['files'] as $path => $components) {
            $remainingComponents = $this->removeComponentFromList($components);

            if (empty($remainingComponents)) {
                $this->deleteFile($path);
                unset($sheafLock['files'][$path]);
                $this->message("+ Removed File: $path");
            } else {
                $sheafLock['files'][$path] = $remainingComponents;
            }
        }
    }

    protected function cleaningDependencies(&$sheafLock)
    {
        if (!isset($sheafLock['internalDependencies'])) {
            return;
        }

        foreach ($sheafLock['internalDependencies'] as $dep => $components) {

            if(!in_array($this->componentName, $components, true)) {
                continue;
            }
            $remainingComponents = $this->removeComponentFromList($components);

            $sheafLock['internalDependencies'][$dep] = $remainingComponents;
            
            if (!empty($remainingComponents)) {
                continue;
            }

            $confirm = confirm(label: "$dep is no longer used as a dependency, would you like to remove it?", default: false, hint: "If you use $dep somewhere else in your project, select no.");

            if (!$confirm) {
                continue;
            }
            
            $remover = new self($this->command);
            $remover->remove($dep);
            $this->message("+ Removed internal dependency: $dep (no longer used.)");
            unset($sheafLock['internalDependencies'][$dep]);
        }
    }

    protected function cleaningHelpers(&$sheafLock)
    {
        if (!isset($sheafLock['helpers'])) {
            return;
        }

        foreach ($sheafLock['helpers'] as $helper => $components) {
            $remainingComponents = $this->removeComponentFromList($components);

            if (empty($remainingComponents)) {
                $this->deleteHelperFile($helper);
                unset($sheafLock['helpers'][$helper]);
                $this->message("+ Removed helper: $helper (no longer used.)");
            } else {
                $sheafLock['helpers'][$helper] = $remainingComponents;
            }
        }
    }

    protected function updateSheafFile()
    {
        $sheafFile = SheafConfig::getSheafFile();

        unset($sheafFile['components'][$this->componentName]);

        SheafConfig::saveSheafFile($sheafFile);
    }

    protected function removeComponentFromList($components)
    {
        return array_values(array_filter($components, fn($c) => $c !== $this->componentName));
    }

    protected function deleteFile($path)
    {
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    protected function deleteHelperFile($helper)
    {
        $path = resource_path("views/components/ui/$helper.blade.php");

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    protected function message(string $message)
    {
        $this->command->info("<fg=green>$message</fg=green>");
    }
}
