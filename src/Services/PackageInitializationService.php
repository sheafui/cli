<?php

namespace Sheaf\Cli\Services;

use Exception;
use Sheaf\Cli\Support\InitializationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class PackageInitializationService
{

    protected ContentTemplateService $contentTemplateService;
    public function __construct(
        protected Command $command,
        protected InitializationConfig $initConfig
    ) {
        $this->contentTemplateService = new ContentTemplateService();
        $this->validateConfiguration();
    }

    /**
     * Initialize the entire Sheaf package with all dependencies
     */
    public function initializePackage()
    {
        try {
            $javascriptAssets = new JavaScriptAssetService(
                command: $this->command,
                forceOverwrite: $this->initConfig->shouldForceOverwrite(),
                shouldSetupLivewire: $this->initConfig->shouldSetupLivewire()
            );

            $this->installComposerDependencies();

            $javascriptAssets->setupAppJs();

            $this->createCssThemeFile();

            if ($this->initConfig->shouldEnableDarkMode()) {
                $javascriptAssets->createDarkModeAssets();
            }

            return true;
        } catch (\Throwable $th) {
            $this->command->error("Initialize Sheaf Package Failed.\n\nIssue: " . $th->getMessage());
            return false;
        }
    }

    public function installComposerDependencies()
    {
        $packages = ['wireui/heroicons'];

        if ($this->initConfig->shouldInstallPhosphorIcons()) {
            $packages[] = 'wireui/phosphoricons';
        }

        if ($this->initConfig->shouldSetupLivewire()) {
            $packages[] = 'livewire/livewire';
        }


        foreach ($packages as $package) {
            if ($this->isComposerPackageInstalled($package)) {
                continue;
            }

            $result = spin(
                callback: fn() => Process::run("composer require $package"),
                message: "Installing $package..."
            );

            if ($result->failed()) {
                $this->command->error("Failed to install $package -" . $result->errorOutput());
            } else {
                $this->command->line("<fg=green>✓ $package Installed.</fg=green>");
            }
        }
    }

    /**
     * Create a theme.css file and import it
     */
    protected function createCssThemeFile()
    {
        $themeFile = resource_path("css/{$this->initConfig->getThemeFileName()}");
        $appCssFile = resource_path("css/{$this->initConfig->getTargetCssFile()}");

        // Create Theme Css file

        $themeContent = $this->contentTemplateService->generateThemeCss($this->initConfig->shouldEnableDarkMode());

        File::put($themeFile, $themeContent);
        $this->command->info('✓ Created theme file: ' . $themeFile);

        // Add import to main CSS file if it exists
        if (File::exists($appCssFile)) {
            $this->addImportToAppCssFile($appCssFile);
        } else {
            $this->command->warn("Main CSS file '{$this->initConfig->getTargetCssFile()}' not found in resources/css/");
            $this->command->info("You can manually import the theme by adding this line to your main CSS file:");
            $this->command->line("@import './theme.css';");
        }
    }

    /**
     * Validate constructor configuration
     */
    protected function validateConfiguration(): void
    {
        $themeFile = $this->initConfig->getThemeFileName();
        $cssFile = $this->initConfig->getTargetCssFile();

        if (empty($themeFile)) {
            throw new Exception('Theme file name cannot be empty');
        }

        if (empty($cssFile)) {
            throw new Exception('Target CSS file name cannot be empty');
        }
    }

    /**
     * Add import statement to main CSS file
     */
    protected function addImportToAppCssFile($path)
    {
        $content = File::get($path);

        // Check if import already exists
        if (
            strpos($content, "@import './{$this->initConfig->getThemeFileName()}'") === false
        ) {
            // Add import at the beginning
            $importStatement = "@import './{$this->initConfig->getThemeFileName()}'; /* By Sheaf.dev */ \n";
            $newContent = $importStatement . $content;
        }

        if (strpos($content, '@custom-variant') === false) {
            $newContent .= "\n\n @custom-variant dark (&:where(.dark, .dark *)); /* By Sheaf.dev */ \n";
        }

        File::put($path, $newContent);
    }

    public function isComposerPackageInstalled($packageName)
    {
        $composerJsonPath = base_path('composer.json');

        if (!File::exists($composerJsonPath)) {
            return false;
        }

        $composerJson = json_decode(File::get($composerJsonPath), true);

        // Check in require
        if (isset($composerJson['require'][$packageName])) {
            return true;
        }

        // Check in require-dev
        if (isset($composerJson['require-dev'][$packageName])) {
            return true;
        }

        return false;
    }
}
