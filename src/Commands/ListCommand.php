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
        //todo: get list of all existing and published components from Fluxtor-dev
        $serverUrl = config('fluxtor-cli.server_url');
        
        $list = Http::get($serverUrl . '/cli/list');
        $this->info("Listing available components: ");
        $this->info($list);
    }
}
