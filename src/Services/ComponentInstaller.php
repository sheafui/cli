<?php

namespace Fluxtor\Cli\Services;

use Fluxtor\Cli\Strategies\Installation\InstallationStrategyFactory;
use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;

class ComponentInstaller
{
    protected ComponentHttpClient $componentHttpClient;
    protected string $name = '';

    public function __construct(
        protected Command $command,
        protected $components,
        protected InstallationConfig $installationConfig
    ) {
        $this->componentHttpClient = new ComponentHttpClient();
        
    }

    public function install(string $componentName)
    {
        try {

            $this->name = $componentName;

            $componentResources = $this->componentHttpClient->fetchResources($componentName);

            $strategy = InstallationStrategyFactory::create(
                $this->installationConfig,
                $this->command,
                $this->components,
                $componentName
            );

            return $strategy->execute(collect($componentResources['data']));
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());

            if (config('fluxtor.env') !== 'production') {
                $this->components->error($th->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    
}
