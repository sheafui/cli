<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\ComponentInstaller;
use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class InstallComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:install 
    {name?           : the name of the component.} 
    {--force         : override the component file if it exist.} 
    {--internal-deps : installing required internal Dependencies.} 
    {--external-deps : installing required external Dependencies.}
    {--dry-run       :  Preview what will be installed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Installing a fluxtor Component';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $componentName = $this->getComponentName();
        $force = $this->option("force");
        $internalDeps = $this->option("internal-deps");
        $externalDeps = $this->option("external-deps");
        $isDryRun = $this->option("dry-run");
        $installationConfig = new InstallationConfig($force, $internalDeps, $externalDeps, $isDryRun);

        $componentInstaller = new ComponentInstaller($this, $this->components, $installationConfig);
        $componentInstaller->install($componentName);
    }

    private function getComponentName()
    {
        $componentName = $this->argument('name');

        if (!$componentName) {
            $componentName = text(label: 'Type the component name', placeholder: 'simple-search', required: true);
        }

        return $componentName;
    }
}
