<?php


namespace Sheaf\Cli\Services;

use Illuminate\Support\Facades\File;

class ComponentRemover
{


    public function remove($name)
    {

        $componentDirectory = resource_path("views/components/ui/$name");

        if (File::isDirectory($componentDirectory)) {
            File::deleteDirectory($componentDirectory);
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
            }else {
                $sheafLock['internalDependencies'][$dep] = $components;
            }
        }

        foreach ($sheafLock['helpers'] as $helper => $components) {
            $components = array_values(array_filter($components, fn($c) => $c !== $name));

            if (empty($components)) {
                File::delete(resource_path("views/components/ui/$helper.blade.php"));
                unset($sheafLock['helpers'][$helper]);
            }else {
                $sheafLock['helpers'][$helper] = $components;
            }
        }

        File::put($sheafLockPath, json_encode($sheafLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
