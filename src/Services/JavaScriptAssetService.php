<?php

namespace Fluxtor\Cli\Services;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class JavaScriptAssetService
{
    protected ContentTemplateService $contentTemplateService;

    public function __construct(
        protected Command $command,
        protected bool $forceOverwrite = false,
        protected bool $shouldSetupLivewire = false
    ) {
        $this->contentTemplateService = new ContentTemplateService();
    }

    /**
     * Create JavaScript files for dark mode functionality
     */
    public function createDarkModeAssets(): bool
    {
        try {
            $this->ensureDirectoryStructure();

            $utilsCreated = $this->createUtilsFile();
            $themeCreated = $this->createThemeFile();

            if ($utilsCreated || $themeCreated) {
                $this->displayCreationSummary($utilsCreated, $themeCreated);
                $this->addImportToAppJsFile();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->command->error("Failed to create JavaScript assets: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Set up the app.js file
     */
    public function setupAppJs()
    {
        if ($this->shouldSetupLivewire) {
            $this->SetupLivewire();
            return;
        }

        $this->installAndSetupAlpine();
    }

    public function installAndSetupAlpine()
    {
        if (!$this->isNpmPackageInstalled('alpinejs')) {
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
            $result = Process::run("npm install alpinejs @alpinejs/anchor");

            if ($result->failed()) {
                $this->command->error("Failed to install Alpine.js. {$result->errorOutput()}");
                return;
            }
        }

        $appJsPath = $this->getMainJsFilePath();

        $appJsContent = File::get($appJsPath);

        if ($this->isAlpineAlreadyImported($appJsContent)) {
            return;
        }

        $alpineInitialize = $this->contentTemplateService->getStubContent('alpinejs');

        $newContent = $appJsContent . $alpineInitialize;

        File::put($appJsPath, $newContent);
    }

    /**
     * Set up livewire scripts
     */
    public function SetupLivewire()
    {
        $appJsPath = $this->getMainJsFilePath();
        $appJsContent = File::get($appJsPath);

        if (preg_match('/import\s+{[^}]*\bLivewire\b[^}]*\bAlpine\b[^}]*}/i', $appJsContent)) {
            return;
        }

        $livewireStarterKit = $this->contentTemplateService->getStubContent('livewirejs');

        $newContent = $appJsContent . $livewireStarterKit;

        File::put($appJsPath, $newContent);
    }

    /**
     * Create utils.js file
     */
    protected function createUtilsFile(): bool
    {
        $path = resource_path("js/utils.js");

        if (File::exists($path) && !$this->forceOverwrite) {
            $this->command->warn("File already exists: utils.js");

            if (!$this->command->confirm('Overwrite existing utils.js file?', false)) {
                return false;
            }
        }

        $content = $this->contentTemplateService->getStubContent('utils.js');

        File::put($path, $content);

        $this->command->info("✓ Created: resources/js/utils.js");
        return true;
    }

    /**
     * Create theme.js file in globals directory
     */
    protected function createThemeFile(): bool
    {
        $filePath = resource_path("js/globals/theme.js");

        if (File::exists($filePath) && !$this->forceOverwrite) {
            $this->command->warn("File already exists: globals/theme.js");

            if (!$this->command->confirm('Overwrite existing theme.js file?', false)) {
                return false;
            }
        }

        $content = $this->contentTemplateService->getStubContent('theme.js');
        File::put($filePath, $content);

        $this->command->info("✓ Created: resources/js/globals/theme.js");
        return true;
    }

    /**
     * Ensure directory structure exists
     */
    protected function ensureDirectoryStructure(): void
    {
        $baseDir = resource_path('js');
        $globalsDir = "{$baseDir}/globals";

        if (!File::exists($globalsDir)) {
            File::makeDirectory($globalsDir, 0755, true);
            $this->command->info('Created directory: resources/js/globals');
        }
    }

    /**
     * Display creation summary
     */
    protected function displayCreationSummary(bool $utilsCreated, bool $themeCreated): void
    {
        $this->command->newLine();
        $this->command->line('<fg=green>JavaScript Assets Created:</fg=green>');

        if ($utilsCreated) {
            $this->command->line('  ✓ utils.js - Alpine.js reactive magic property utilities');
        }

        if ($themeCreated) {
            $this->command->line('  ✓ globals/theme.js - Dark mode theme management system');
        }
    }

    /**
     * Provide import instructions for the created files
     */
    protected function addImportToAppJsFile(): void
    {
        $file = 'app.js';
        $path = $this->getMainJsFilePath();

        $content = File::get($path);

        // Check if import already exists
        if (!preg_match('/^import\s+[\'"]\.\/globals\/theme\.js[\'"]/', $content)) {
            $importStatement = "import './globals/theme.js'; /* By Fluxtor.dev */ \n\n";
            $content = $importStatement . $content;
        }

        File::put($path, $content);

        $this->command->info("Added import statement to: {$file}");
    }

    public function getMainJsFilePath()
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

        return $path;
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
}
