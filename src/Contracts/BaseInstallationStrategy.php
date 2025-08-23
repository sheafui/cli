<?php

namespace Fluxtor\Cli\Contracts;

use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;

abstract class BaseInstallationStrategy implements InstallationStrategyInterface
{
    public function __construct(
        protected Command $command,
        protected InstallationConfig $installationConfig,
        protected $consoleComponent,
        protected string $componentName
    ) {}

    protected function reportSuccess(): void
    {
        $name = $this->installationConfig->componentHeadlineName();
        $this->command->line(" <bg=green;fg=black> $name </bg=green;fg=black> <fg=white>has been installed successfully.</fg=white>");
    }
}