<?php

namespace Fluxtor\Cli\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
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
        $componentName = $this->getComponentName();

        try {
            $componentResources = $this->fetchComponentResources($componentName);
            
            // dd($componentResources);

            $createdFiles = $this->addComponentFiles($componentResources);

            $component = Str::of($componentName)->replace('-', ' ')->title();

            $this->components->info($component . ' has been added.');

            foreach ($createdFiles as $file) {
                $this->components->info($file['path'] . ' has been ' . $file['action']);
            }

        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());
        }
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

    private function getComponentName()
    {
        $componentName = $this->argument('name');

        if (!$componentName) {
            $componentName = text(label: 'Type the component name', placeholder: 'simple-search', required: true);
        }

        return $componentName;
    }

    private function fetchComponentResources(string $componentName)
    {
        $serverUrl = config('fluxtor.cli.server_url');

        $result = Http::get($serverUrl . '/api/cli/components/' . $componentName);

        if ($result->failed()) {
            throw new Exception('Faield to add the component.');
        }

        return json_decode($result);
        
    }

    private function addComponentFiles($componentResources)
    {
        $createdFiles = [];

        collect($componentResources->files)->each(function ($file) use (&$createdFiles) {
            $filePath = $file->path;

            if (!file_exists($filePath)) {
                $this->createComponentFile($filePath, $file->content);
                $createdFiles[] = ['path' => $filePath, 'action' => 'created'];
            } else {
                $shouldOverride = confirm(label: $filePath . ' File already exists, do you want to overide it?');

                if (!$shouldOverride) {
                    $createdFiles[] = ['path' => $filePath, 'action' => 'skipped'];
                    return;
                }

                $this->createComponentFile($filePath, $file->content);

                $createdFiles[] = ['path' => $filePath, 'action' => 'overrided'];
            }
        });

        return $createdFiles;
    }
}
