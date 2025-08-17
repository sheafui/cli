<?php

namespace Fluxtor\Cli\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

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
                $result = Process::run("composer require $package");

                if ($result->failed()) {
                    $this->command->error("Failed to install $package " . $result->errorOutput());
                }
            }
        }
    }

    public function installNodeDependencies()
    {
        // Check if Alpine.js available
        if (!$this->isNpmPackageInstalled('alpinejs')) {
            $this->command->warn("Missing a required package: Alpinejs");
            $this->command->info("Installing Alpinejs...");
            Process::run("npm install alpinejs");

            $appJsContent = File::get(resource_path('/js/app.js'));

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
