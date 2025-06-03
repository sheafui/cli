<?php

namespace Fluxtor\Cli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available commponents';

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

            $list = $response->collect();

            $list->each(function ($component) {
                $this->components->twoColumnDetail($component['name'] . ':', $component['description']);
                
            });
        } catch (\Throwable $th) {
            $this->components->error('Something went wrong');
            $this->components->error('Error details: ' . $th->getMessage());
        }
    }
}
