<?php

namespace Sheaf\Cli\Commands;

use Sheaf\Cli\Services\ComponentInstaller;
use Sheaf\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

class InstallComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheaf:install 
    {name?*          : the name of the component.} 
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
    protected $description = 'Installing a sheaf Component';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $componentNames = $this->getComponentName();
        $installationConfig = new InstallationConfig(
            force: $this->option("force"),
            skipDeps: $this->option("skip-deps"),
            onlyDeps: $this->option("only-deps"),
            internalDeps: $this->option("internal-deps"),
            externalDeps: $this->option("external-deps"),
            isDryRun: $this->option("dry-run")
        );

        foreach ($componentNames as $name) {
            $title = $this->getBannerTitle($name);

            $this->banner($title);
            $installationConfig->setComponentName($name);

            $result = (new ComponentInstaller($this, $this->components, $installationConfig))->install($name);

            if ($result === Command::SUCCESS) {
                $this->components->info("Full documentation: https://sheafui.dev/docs/components/{$name}");
            }
        }
    }

    private function getComponentName()
    {
        $componentName = $this->argument('name');


        if (!$componentName) {
            $componentName = text(label: 'What are the component(s) you would like to install or update?', placeholder: 'button', required: true);
        }


        return Arr::wrap($componentName);
    }

    public function banner(string $title): void
    {
        $length = strlen("  {$title}") + 4;

        $this->newLine();
        $this->line(str_repeat("═", $length));
        $this->line("  {$title}");
        $this->line(str_repeat("═", $length));
        $this->newLine();
    }

    public function getBannerTitle(string $title)
    {
        $formattedTitle = Str::of($title)->headline();

        return match (true) {
            $this->option('only-deps') => "Installing {$formattedTitle} Dependencies Only",
            $this->option('dry-run') => "Preview: Installing {$formattedTitle} (Dry Run)",
            $this->option('skip-deps') => "Installing {$formattedTitle} Files Only",
            $this->option("force") => "Installing {$formattedTitle} (Force Mode)",
            default => "Installing {$formattedTitle}"
        };
    }
}
