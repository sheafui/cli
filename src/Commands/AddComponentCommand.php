<?php

namespace Fluxtor\Cli\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
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
    protected $signature = 'fluxtor:add {name? : the name of the component.} {--force : override the component file if it exist.}';

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
        $this->addComponent($componentName);
    }

    private function addComponent(string $componentName)
    {
        try {
            $componentResources = $this->fetchComponentResources($componentName);

            $createdFiles = $this->addComponentFiles($componentResources->get('files'));

            $component = Str::of($componentName)->replace('-', ' ')->title();

            $this->components->info($component . ' has been added.');

            foreach ($createdFiles as $file) {
                $this->components->info($file['path'] . ' has been ' . $file['action']);
            }

            $dependencies = $componentResources->get('dependencies');

            $this->handleDependencies($dependencies);
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());
        }
    }

    private function createComponentFile(string $filePath, string $fileContent)
    {
        $directory = str($filePath)->beforeLast('/');
        File::ensureDirectoryExists($directory);
        File::replace($filePath, $fileContent);
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

        return Http::get($serverUrl . '/api/cli/components/' . $componentName)
            ->onError(function ($res) use ($componentName) {
                $component = Str::of($componentName)->replace('-', ' ')->title();
                $this->components->error('Failed to add the component "' . $component . '" ' . $res->json());
                exit(1);
            })
            ->collect();
    }

    private function addComponentFiles($files)
    {
        $createdFiles = [];
        $forceFileCreation = $this->option('force');

        foreach ($files as $file) {
            $filePath = $file['path'];
            $content = $file['content'];

            if (!file_exists($filePath)) {
                $this->createComponentFile($filePath, $content);
                $createdFiles[] = ['path' => $filePath, 'action' => 'created'];
                continue;
            }

            $shouldOverride = $forceFileCreation ? true : confirm($filePath . ' File already exists, do you want to override it?');

            if (!$shouldOverride) {
                $createdFiles[] = ['path' => $filePath, 'action' => 'skipped'];
                continue;
            }

            $this->createComponentFile($filePath, $content);

            $createdFiles[] = ['path' => $filePath, 'action' => 'overridden'];
        }

        return $createdFiles;
    }

    private function handleDependencies($dependencies)
    {
        if (!$dependencies) {
            return;
        }

        if ($depInternal = $dependencies['internal']) {
            $depInternal = Arr::wrap($depInternal);

            $installDependencies = confirm(label: 'This component need dependencies to work as expected, do you want to install them?', default: true);

            if ($installDependencies) {
                $this->components->info("↳ Installing internal dependency: $depInternal");
                foreach ($depInternal as $dep) {
                    $this->addComponent($dep);
                }
            }
        }

        // if($depExternal = $dependencies['external']) {
        //     $this->components->warn("This component has an external dependencies must be installed to work.");
        //     dump($depExternal);
        // }
    }
}
