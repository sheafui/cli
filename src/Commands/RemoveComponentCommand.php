<?php

namespace Sheaf\Cli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Sheaf\Cli\Services\ComponentRemover;

use function Laravel\Prompts\text;

class RemoveComponentCommand extends Command
{

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
        $success = Command::SUCCESS;

        $componentRemover = new ComponentRemover($this);

        foreach ($componentNames as $name) {

            $this->banner("Removing all $name files");

            $success = $componentRemover->remove($name);
        }

        if($success === Command::SUCCESS) {
            $this->info("+ updated sheaf-lock.json and sheaf.json files");
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
