<?php

namespace Fluxtor\Cli\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class PackageInitializationService
{

    protected ContentTemplateService $contentTemplateService;
    public function __construct(
        protected Command $command,
        protected $enablePhosphorIcons = false,
        protected $enableDarkMode = false,
        protected $themeFileName,
        protected $targetCssFile,
        protected string|null $jsDirectory,
        protected $forceOverwrite
    ) {
        $this->contentTemplateService = new ContentTemplateService();
        $this->validateConfiguration();
    }

    /**
     * Initialize the entire Fluxtor package with all dependencies
     */
    public function initializePackage()
    {
        try {
            $this->installComposerDependencies();

            $this->createCssThemeFile();

            $this->installNodeDependencies();

            if ($this->enableDarkMode) {

                (new JavaScriptAssetService($this->command, $this->jsDirectory))->createDarkModeAssets();
            }

            return true;
        } catch (\Throwable $th) {
            $this->command->error("Initialize Fluxtor Package Failed.\n\nIssue: " . $th->getMessage());
            return false;
        }
    }

    /**
     * Validate constructor configuration
     */
    protected function validateConfiguration(): void
    {
        if (empty($this->themeFileName)) {
            throw new Exception('Theme file name cannot be empty');
        }

        if (empty($this->targetCssFile)) {
            throw new Exception('Target CSS file name cannot be empty');
        }

        if ($this->enableDarkMode && empty($this->jsDirectory)) {
            throw new Exception('JavaScript directory is required when dark mode is enabled');
        }

        // Ensure file names have proper extensions
        if (!str_ends_with($this->themeFileName, '.css')) {
            $this->themeFileName .= '.css';
        }

        if (!str_ends_with($this->targetCssFile, '.css')) {
            $this->targetCssFile .= '.css';
        }
    }

    /**
     * Create a theme.css file and import it
     */
    protected function createCssThemeFile()
    {
        $themeFile = resource_path("css/{$this->themeFileName}");
        $appCssFile = resource_path('css/' . $this->targetCssFile);

        // Create Theme Css file

        $themeContent = $this->contentTemplateService->generateThemeCss($this->enableDarkMode);

        File::put($themeFile, $themeContent);
        $this->command->info('Created theme file: ' . $themeFile);

        // Add import to main CSS file if it exists
        if (File::exists($appCssFile)) {
            $this->addImportToAppCssFile($appCssFile);
        } else {
            $this->command->warn("Main CSS file '{$this->targetCssFile}' not found in resources/css/");
            $this->command->info("You can manually import the theme by adding this line to your main CSS file:");
            $this->command->line("@import 'theme.css';");
        }
    }

    /**
     * Add import statement to main CSS file
     */
    protected function addImportToAppCssFile($targetCssFile)
    {
        $appCssContent = File::get($targetCssFile);

        // Check if import already exists
        if (
            strpos($appCssContent, "@import './{$this->themeFileName}'") !== false
        ) {
            $this->command->info('Import statement already exists in main CSS file.');
            return;
        }

        // Add import at the beginning
        $importStatement = "@import './{$this->themeFileName}'; /* By Fluxtor.dev */ \n\n";
        $newContent = $importStatement . $appCssContent;

        File::put($targetCssFile, $newContent);
        $this->command->info("Added import statement to: {$targetCssFile}");
    }

    public function installComposerDependencies()
    {
        $packages = ['wireui/heroicons'];

        if ($this->enablePhosphorIcons) {
            $packages[] = 'wireui/phosphoricons';
        }


        foreach ($packages as $package) {
            if (!$this->isComposerPackageInstalled($package)) {
                $this->command->info("Installing $package...");
                $result = Process::forever()->run("composer require $package", function (string $type, string $output) {
                    echo $output;
                });

                if ($result->failed()) {
                    $this->command->error("Failed to install $package " . $result->errorOutput());
                }
            }
        }
    }

    public function installNodeDependencies()
    {
        $isUsingLivewire = confirm(
            label: "Will this project use Livewire?",
            default: false,
            hint: "Choose 'yes' if your project is using Livewire v2 or v3."
        );

        // Check if Alpine.js available
        if (!$isUsingLivewire && !$this->isNpmPackageInstalled('alpinejs')) {
            $needsAlpine = confirm(
                label: "Alpine.js is not installed. Would you like to install it now?",
                default: true,
                hint: "Alpine.js is required for interactive components. Skipping may cause some features to break."
            );

            if (!$needsAlpine) {
                $this->command->warn("Alpine.js installation skipped. Some UI components may not function correctly.");
                return;
            }

            $this->command->info("Installing Alpinejs...");
            Process::forever()->run("npm install alpinejs @alpinejs/anchor", function (string $type, string $output) {
                echo $output;
            });

            $appJsContent = $this->getMainJsFile();

            if (!$this->isAlpineAlreadyImported($appJsContent)) {
                $alpineInitialize = $this->contentTemplateService->getStubContent('alpinejs');

                $newContent = $appJsContent . $alpineInitialize;

                if (!File::exists(resource_path('/js/app.js'))) {
                    $this->command->warn('Missing app.js file.');

                    File::makeDirectory(resource_path('/js/app.js'), 0755, true);

                    $this->command->info('app.js file created: resources/js/app.js');
                }

                File::put(resource_path('/js/app.js'), $newContent);
            }
        }
    }


    public function getMainJsFile()
    {
        $path = resource_path('/js/app.js');
        if (!File::exists($path)) {
            $path = text(
                label: "Enter the path (relative to resources/) to your main JS file:",
                placeholder: '/js/app.js'
            );
        }

        if (! File::exists($path)) {
            throw new Exception("The file '{$path}' does not exist in resources/. Please create it or specify the correct path.");
        }

        return File::get($path);
    }
    /**
     * Check if Alpine.js is already imported
     */
    protected function isAlpineAlreadyImported(string $content): bool
    {
        $patterns = [
            '/import\s+.*alpine/i',
            '/require\s*\(\s*[\'"]alpine/i',
            '/from\s+[\'"]alpinejs[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    public function isNpmPackageInstalled($packageName)
    {
        $packageJsonPath = base_path('package.json');

        if (!file_exists($packageJsonPath)) {
            return false;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);

        // Check in dependencies
        if (isset($packageJson['dependencies'][$packageName])) {
            return true;
        }

        // Check in devDependencies
        if (isset($packageJson['devDependencies'][$packageName])) {
            return true;
        }

        return false;
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
