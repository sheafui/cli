<?php

namespace Fluxtor\Cli\Strategies\Installation;

use Fluxtor\Cli\Contracts\BaseInstallationStrategy;
use Fluxtor\Cli\Traits\CanHandleDependenciesInstallation;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DryRunStrategy extends BaseInstallationStrategy
{
    use CanHandleDependenciesInstallation;

    public function execute(Collection $componentResources): int
    {
        $files = $componentResources->get('files', []);
        $dependencies = $componentResources->get('dependencies', []);

        foreach ($files as $file) {
            $this->command->info("<fg=white>Will create: {$file['path']}</fg=white>");
        }

        if (!empty($dependencies['internal'])) {
            $this->installDependencies(Arr::wrap($dependencies['internal']));
        }

        return Command::SUCCESS;
    }
}
