<?php

namespace Fluxtor\Cli\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;

class ComponentInstaller
{
    public function __construct(protected $components, protected $force) {}

    public function addComponent(string $componentName)
    {
        try {
            $componentResources = $this->fetchComponentResources($componentName);

            $createdFiles = $this->addComponentFiles($componentResources->get('files'));

            $component = Str::of($componentName)->headline();

            $this->installingReport($component, $createdFiles);

            $this->handleDependencies($componentResources->get('dependencies'));
        } catch (\Throwable $th) {
            $this->components->error($th->getMessage());

            if(!app()->isProduction()){
                $this->components->error($th->getTraceAsString());
            }
        }
    }

    private function installingReport(string $component, array $createdFiles) {
        $this->components->info($component . ' has been added.');

            foreach ($createdFiles as $file) {
                $this->components->info($file['path'] . ' has been ' . $file['action']);
            }
    }

    private function createComponentFile(string $filePath, string $fileContent)
    {
        $directory = str($filePath)->beforeLast('/');
        File::ensureDirectoryExists($directory);
        File::replace($filePath, $fileContent);
    }

    private function fetchComponentResources(string $componentName)
    {
        $serverUrl = config('fluxtor.cli.server_url');

        $token = FluxtorConfig::getUserToken();

        if(!$token) {
            $this->components->error("You need to login, Please run 'php artisan fluxtor:login' and login with you fluxtor account.");
            return;
        }

        return Http::withToken($token)->get($serverUrl . '/api/cli/components/' . $componentName)
            ->onError(function ($res) use ($componentName) {
                $component = Str::of($componentName)->headline();
                $responseJson = $res->json()['message'];
                
                $this->components->error("Failed to add the component '$component' $responseJson.");
                exit(1);
            })
            ->collect();
    }

    private function addComponentFiles($files)
    {
        $createdFiles = [];
        $forceFileCreation = $this->force;

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

        if ($depInternal = Arr::wrap($dependencies['internal'])) {
            $installDependencies = confirm(label: 'This component need dependencies to work as expected, do you want to install them?', default: true);

            if (!$installDependencies) {
                return;
            }

            $this->components->info('↳ Installing internal dependency');
            foreach ($depInternal as $dep) {
                $this->addComponent($dep);
            }
        }

        if ($depExternal = Arr::wrap($dependencies['external'])) {

            $this->components->warn('This component has an external dependencies must be installed to work.');
            $confirmInstall = confirm(label: 'This component need an external dependencies to work, do you want to install them?', default: true);

            if (!$confirmInstall) {
                return;
            }

            foreach ($depExternal as $key => $dep) {
                $this->components->info("↳ Installing $key...");
                Process::run($dep[1]);
            }
        }
    }
}
