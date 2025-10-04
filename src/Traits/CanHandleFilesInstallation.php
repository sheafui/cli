<?php

namespace Sheaf\Cli\Traits;

use Sheaf\Cli\Support\InstallationConfig;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;

trait CanHandleFilesInstallation
{

    protected InstallationConfig $installationConfig;

    public function initInstallationConfigForFilesInstallation(InstallationConfig $installationConfig)
    {
        $this->installationConfig = $installationConfig;
    }

    public function installFiles($files)
    {
        $createdFiles = [];
        $forceFileCreation = $this->installationConfig->shouldForceOverwriting();

        foreach ($files as $file) {
            $filePath = $file['path'];
            $content = $file['content'];

            if (!file_exists($filePath)) {
                $this->createComponentFile($filePath, $content);
                $createdFiles[] = ['path' => $filePath, 'action' => 'created'];
                continue;
            }


            $shouldOverride = $forceFileCreation;

            if (!$shouldOverride) {
                $shouldOverride = confirm(
                    label: "File already exists: $filePath. Overwrite?",
                    hint: $this->installationConfig->isComponentOutdated() ?
                        "Component updates available. Reinstall recommended to ensure you have the latest files."
                        : ""
                );
            }

            if (!$shouldOverride) {
                $createdFiles[] = ['path' => $filePath, 'action' => 'skipped'];
                continue;
            }

            $this->createComponentFile($filePath, $content);

            $createdFiles[] = ['path' => $filePath, 'action' => 'overwritten'];
        }

        return $createdFiles;
    }

    private function createComponentFile(string $filePath, string $fileContent)
    {
        $path = base_path($filePath);

        $directory = str($path)->beforeLast('/');
        File::ensureDirectoryExists($directory);
        File::replace($path, $fileContent);
    }
}
