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
                $this->error('Failed: ' . $response->reason());
                return;
            }

            $list = $response->collect();

            $list->each(function ($component) {
                $this->warn($component['name'] . ':');
                $this->info($component['description']);
                $this->info('');
            });
        } catch (\Throwable $th) {
            $this->error('Something went wrong');
            $this->warn('Error details: ' . $th->getMessage());
        }
    }
}
