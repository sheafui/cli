<?php

namespace Sheaf\Cli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheaf:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available components';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $serverUrl = config('sheaf.cli.server_url');

            $response = Http::get($serverUrl . '/api/cli/list');

            if ($response->failed()) {
                $this->components->error('Failed: ' . $response->reason());
                return;
            }

            $components = $response->collect();

            $this->displayComponents($components);

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->components->error('Something went wrong');
            $this->components->error('Error details: ' . $th->getMessage());
        }
    }


    private function displayComponents($components)
    {
        $this->newLine();
        $this->line('<fg=cyan>Available Components:</fg=cyan>');
        $this->line(str_repeat('=', 50));

        foreach ($components as $component) {
            $this->line(' ✦ <fg=white;options=bold>' . $component['name'] . '</fg=white;options=bold>');
            $this->line('  ' . $component['description']);
            $this->newLine();
        }
    }
}
