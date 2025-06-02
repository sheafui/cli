<?php

namespace Fluxtor\Cli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class AddComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:add {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a fluxtor Component';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverUrl = config('fluxtor.cli.server_url');

        $componentName = $this->argument("name");

        $component = Http::get($serverUrl . '/api/cli/components/' . $componentName);
        
        if($component->failed()) {
            $this->error("Failed: " . $component->reason());
            return;
        }

        collect($component['files'])->each(function ($file) {
            $filePath = $file['path'];
            if(!file_exists($filePath)) {
                File::replace($filePath, $file['content']);

                $this->info("File has been created at " . $filePath);
            }else {
                dump($filePath . " File exist. should be ovveriding?");
            }
        });

    }
}
