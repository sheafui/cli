<?php


namespace Sheaf\Cli\Services;

use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\ConsoleOutput;


class ComponentRemover
{
    protected $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }

    public function remove($name)
    {

        $componentDirectory = resource_path("views/components/ui/$name");

        if (File::isDirectory($componentDirectory)) {
            File::deleteDirectory($componentDirectory);
            $this->message("+ Deleted directory: $componentDirectory");
        }

        $this->cleaningSheafLock($name);
    }

    protected function cleaningSheafLock($name)
    {
        $sheafLockPath = base_path('sheaf-lock.json');


        $sheafLock = [];

        if (File::exists($sheafLockPath)) {
            $sheafLock = json_decode(File::get($sheafLockPath), true) ?: [];
        }

        foreach ($sheafLock['files'] as $path => $components) {
            $components = array_values(array_filter($components, fn($c) => $c !== $name));

            if (empty($components)) {
                File::delete($path);
                unset($sheafLock['files'][$path]);
                $this->message("+ Removed File: $path");
            } else {
                $sheafLock['files'][$path] = $components;
            }
        }

        foreach ($sheafLock['internalDependencies'] as $dep => $components) {
            $components = array_values(array_filter($components, fn($c) => $c !== $name));

            if (empty($components)) {
                $remover = new self();

                $remover->remove($dep);
                unset($sheafLock['internalDependencies'][$dep]);
                $this->message("+ Removed internal dependency: $dep (no longer used.)");
            } else {
                $sheafLock['internalDependencies'][$dep] = $components;
            }
        }

        foreach ($sheafLock['helpers'] as $helper => $components) {
            $components = array_values(array_filter($components, fn($c) => $c !== $name));

            if (empty($components)) {
                File::delete(resource_path("views/components/ui/$helper.blade.php"));
                unset($sheafLock['helpers'][$helper]);
                $this->message("+ Removed helper: $helper (no longer used.)");
            } else {
                $sheafLock['helpers'][$helper] = $components;
            }
        }

        File::put($sheafLockPath, json_encode($sheafLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }


    protected function message(string $message) {
        $this->output->writeln("<fg=green>$message</fg=green>");
    }
}
