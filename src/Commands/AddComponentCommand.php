<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\ComponentInstaller;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class AddComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:add {name? : the name of the component.} {--force : override the component file if it exist.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a fluxtor Component';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $componentName = $this->getComponentName();
        $force = $this->option("force");

        $componentInstaller = new ComponentInstaller($this->components, $force);
        $componentInstaller->addComponent($componentName);

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
