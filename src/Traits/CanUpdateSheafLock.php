<?php

namespace Sheaf\Cli\Traits;

use Illuminate\Support\Arr;
use Sheaf\Cli\Services\SheafConfig;

trait CanUpdateSheafLock {
    protected $name;

    protected function updateSheafLock($files, $dependencies, $name)
    {
        $sheafLock = SheafConfig::loadSheafLock();

        $this->name = $name;

        $this->updateFilesInLock($sheafLock, $files);
        $this->updateDependenciesInLock($sheafLock, $dependencies);

        SheafConfig::saveSheafLock($sheafLock);
    }


    protected function updateFilesInLock(&$sheafLock, $files)
    {
        if(!$files) return;

        $sheafLock['files'] ??= [];

        foreach ($files as $file) {
            if ($this->shouldSkipFile($file['path'])) continue;

            $this->addComponentToLockEntry($sheafLock['files'], $file['path']);
        }
    }

    protected function updateDependenciesInLock(&$sheafLock, $dependencies)
    {
        if(!$dependencies) return;
        
        if (isset($dependencies['helpers'])) {
            $this->addComponentToLockSection($sheafLock, 'helpers', $dependencies['helpers']);
        }

        if (isset($dependencies['internal'])) {
            $this->addComponentToLockSection($sheafLock, 'internalDependencies', Arr::wrap($dependencies['internal']));
        }
    }

    protected function shouldSkipFile($path)
    {
        return str_contains($path, "resources/views/components/ui/{$this->name}");
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
        if (!in_array($this->name, $lockSection[$key], true)) {
            $lockSection[$key][] = $this->name;
        }
    }
}