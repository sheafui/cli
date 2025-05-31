<?php

namespace Fluxtor\Cli\Commands;

use Illuminate\Console\Command;

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
        $this->info("Listing available components");
    }
}
