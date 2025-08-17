<?php

namespace Fluxtor\Cli\Commands;

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
    protected $signature = 'fluxtor:list {--type=all : Filter by component type (free|premium)}';

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
            $serverUrl = config('fluxtor.cli.server_url');

            $response = Http::get($serverUrl . '/api/cli/list');

            if ($response->failed()) {
                $this->components->error('Failed: ' . $response->reason());
                return;
            }

            $components = $response->collect();

            $filteredComponents = $this->filterComponents($components);

            if ($filteredComponents->isEmpty()) {
                $this->components->info('No components found matching your criteria.');
                return Command::SUCCESS;
            }

            $this->displayComponents($filteredComponents);

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->components->error('Something went wrong');
            $this->components->error('Error details: ' . $th->getMessage());
        }
    }


    public function filterComponents(Collection $components)
    {
        $type = $this->option("type");

        if ($type === 'free') {
            return $components->filter(fn($component) => $component['isFree']);
        } elseif ($type === 'premium') {
            return $components->filter(fn($component) => !$component['isFree']);
        }

        return $components;
    }

    private function displayComponents($components)
    {
        $this->newLine();
        $this->line('<fg=cyan>Available Components:</fg=cyan>');
        $this->line(str_repeat('=', 50));

        foreach ($components as $component) {
            $badge = $component['isFree']
                ? '<bg=green;fg=black> FREE </bg=green;fg=black>'
                : '<bg=yellow;fg=black> PREMIUM </bg=yellow;fg=black>';

            $this->line($badge . ' <fg=white;options=bold>' . $component['name'] . '</fg=white;options=bold>');
            $this->line('  ' . $component['description']);
            $this->newLine();
        }
    }
}
