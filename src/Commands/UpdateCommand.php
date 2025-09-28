<?php

namespace Sheaf\Cli\Commands;

use Illuminate\Console\Command;
use Sheaf\Cli\Services\ComponentUpdater;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheaf:update {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the component if is available.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $name = $this->argument('name');

            $this->banner("Update component: $name");

            (new ComponentUpdater($name, $this->components, $this))->handleUpdate();

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->components->error('Something went wrong');
            $this->components->error('Error details: ' . $th->getMessage());
        }
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
