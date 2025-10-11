<?php

namespace Sheaf\Cli\Commands;

use Sheaf\Cli\Services\ComponentInstaller;
use Sheaf\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Sheaf\Cli\Services\ComponentRemover;

use function Laravel\Prompts\text;

class RemoveComponentCommand extends Command
{

    public function __construct(protected ComponentRemover $componentRemover) {
        parent::__construct();
    }
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheaf:remove 
    {name?*          : the name of the component.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removing a sheaf Component';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $componentNames = $this->getComponentName();

        foreach ($componentNames as $name) {

            $this->banner("Removing all $name files");

            $this->componentRemover->remove($name);
        }

        return Command::SUCCESS;
    }

    private function getComponentName()
    {
        $componentName = $this->argument('name');


        if (!$componentName) {
            $componentName = text(label: 'What are the component(s) you would like to remove?', placeholder: 'button', required: true);
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
}
