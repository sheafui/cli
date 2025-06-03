<?php

namespace Fluxtor\Cli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

class AddComponentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:add {name?}';

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

        $componentName = $this->argument('name');

        if (!$componentName) {
            $componentName = text(
                label: 'Type the component name',
                placeholder: 'simple-search',
                required: true,
            );
        }

        

        $result = Http::get($serverUrl . '/api/cli/components/' . $componentName);

        if ($result->failed()) {
            $this->error('Failed: ' . $result->reason());
            return;
        }

        $component = json_decode($result);
        
        collect($component->files)->each(function ($file) {
            $filePath = $file->path;

            if (!file_exists($filePath)) {
                $this->createComponentFile($filePath, $file->content);
                $this->info('File has been created at ' . $filePath);
            } else {
                $this->warn($filePath);
                $shouldOverride = confirm(label: 'This File already exists, do you want to overide it?');

                if (!$shouldOverride) {
                    $this->info('File has been skipped');
                    return;
                }

                $this->createComponentFile($filePath, $file->content);

                $this->info('File has been overided');
            }
        });
    }

    private function createComponentFile(string $filePath, string $fileContent)
    {
        $directory = str($filePath)->beforeLast('/');

        if (File::ensureDirectoryExists($directory)) {
            File::replace($filePath, $fileContent);
        } else {
            File::makeDirectory($directory, 0755, true, true);
            File::replace($filePath, $fileContent);
        }
    }
}
