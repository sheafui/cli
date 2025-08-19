<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\ComponentInstaller;
use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

use function Laravel\Prompts\text;

class InstallComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:install 
    {name?*           : the name of the component.} 
    {--force         : override the component file if it exist.} 
    {--skip-deps     : Skip Dependency Installation.}
    {--only-deps     : Install Only Dependency.}
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

        $componentNames = $this->getComponentName();

        foreach ($componentNames as $name) {
            $this->banner($name);

            $installationConfig = new InstallationConfig(
                name: $name,
                force: $this->option("force"),
                skipDeps: $this->option("skip-deps"),
                onlyDeps: $this->option("only-deps"),
                internalDeps: $this->option("internal-deps"),
                externalDeps: $this->option("external-deps"),
                isDryRun: $this->option("dry-run")
            );

            (new ComponentInstaller($this, $this->components, $installationConfig))->install($name);

            $this->components->info("Full documentation: https://fluxtor.dev/docs/components/{$installationConfig->componentName()}");
        }
    }

    private function getComponentName()
    {
        $componentName = $this->argument('name');


        if (!$componentName) {
            $componentName = text(label: 'Type the component name', placeholder: 'button', required: true);
        }

        return Arr::wrap($componentName);
    }

    public function banner(string $title): void
    {
        $length = strlen("  Installing:  <info>{$title}</info>") + 4;

        $this->newLine();
        $this->line(str_repeat("═", $length));
        $this->line("  Installing:  <info>{$title}</info>");
        $this->line(str_repeat("═", $length));
        $this->newLine();
    }
}
