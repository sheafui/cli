<?php


namespace Sheaf\Cli\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Concerns\InteractsWithIO;
use Laravel\Prompts\Output\ConsoleOutput;
use Symfony\Component\Console\Input\StringInput;

class ComponentRemover
{
    protected $output;
    protected $componentName;

    public function __construct(protected $command)
    {
    }

    public function remove($name)
    {
        $this->componentName = $name;

        $isExists = $this->checkComponentExistence();

        if(!$isExists) {
            $this->message("Component is not installed in this project.");
            return Command::FAILURE;
        }

        $this->deleteComponentFiles();

        $this->cleaningSheafLock($name);
    }

    protected function checkComponentExistence()
    {

        return File::exists(resource_path("views/components/ui/{$this->componentName}")) ||
            File::exists(resource_path("views/components/ui/{$this->componentName}.blade.php"));
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

    protected function cleaningSheafLock($name)
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
        foreach ($sheafLock['internalDependencies'] as $dep => $components) {
            $remainingComponents = $this->removeComponentFromList($components);

            if (empty($remainingComponents)) {
                $remover = new self();

                $remover->remove($dep);
                unset($sheafLock['internalDependencies'][$dep]);
                $this->message("+ Removed internal dependency: $dep (no longer used.)");
            } else {
                $sheafLock['internalDependencies'][$dep] = $remainingComponents;
            }
        }
    }

    protected function cleaningHelpers(&$sheafLock)
    {
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
