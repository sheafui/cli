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
        // curl GET https://127.0.0.1/cli/list
        // curl GET https://127.0.0.1/cli/check
        //todo: get list of all existing and published components from Fluxtor-dev
        $serverUrl = config('fluxtor.cli.server_url');

        $response = Http::get($serverUrl . '/api/components/fasda');
        // $this->info('Listing available components: ');

        // if ($response->failed()) {
        //     $this->warn('Sorry, we have issue to connect to the server.');
        //     return;
        // }

        // $list = $response->collect();

        // // dd($list);

        // $list->each(function ($component) {
        //     $this->warn($component["name"] . ':');
        //     $this->info($component["description"]);
        //     $this->info('');
        // });

        // foreach ($list as $component) {
        //     $this->warn($component->name . ':');
        //     $this->info($component->description);
        //     $this->info('');
        // }
    }
}
