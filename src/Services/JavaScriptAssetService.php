<?php

namespace Fluxtor\Cli\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class JavaScriptAssetService
{
    protected Command $command;
    protected ContentTemplateService $contentTemplateService;
    protected string $jsDirectory;
    protected bool $forceOverwrite;

    public function __construct(Command $command, string $jsDirectory, bool $forceOverwrite = false)
    {
        $this->command = $command;
        $this->jsDirectory = $jsDirectory;
        $this->forceOverwrite = $forceOverwrite;
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
     * Create utils.js file
     */
    protected function createUtilsFile(): bool
    {
        $path = resource_path("js/{$this->jsDirectory}/utils.js");

        if (File::exists($path) && !$this->forceOverwrite) {
            $this->command->warn("File already exists: utils.js");

            if (!$this->command->confirm('Overwrite existing utils.js file?', false)) {
                return false;
            }
        }

        $content = $this->contentTemplateService->getStubContent('utils.js');
        File::put($path, $content);

        $this->command->info("✓ Created: resources/js/{$this->jsDirectory}/utils.js");
        return true;
    }

    /**
     * Create theme.js file in globals directory
     */
    protected function createThemeFile(): bool
    {
        $filePath = resource_path("js/{$this->jsDirectory}/globals/theme.js");

        if (File::exists($filePath) && !$this->forceOverwrite) {
            $this->command->warn("File already exists: globals/theme.js");

            if (!$this->command->confirm('Overwrite existing theme.js file?', false)) {
                return false;
            }
        }

        $content = $this->contentTemplateService->getStubContent('theme.js');
        File::put($filePath, $content);

        $this->command->info("✓ Created: resources/js/{$this->jsDirectory}/globals/theme.js");
        return true;
    }

    /**
     * Ensure directory structure exists
     */
    protected function ensureDirectoryStructure(): void
    {
        $baseDir = resource_path("js/{$this->jsDirectory}");
        $globalsDir = "{$baseDir}/globals";

        if (!File::exists($baseDir)) {
            File::makeDirectory($baseDir, 0755, true);
            $this->command->info("Created directory: resources/js/{$this->jsDirectory}");
        }

        if (!File::exists($globalsDir)) {
            File::makeDirectory($globalsDir, 0755, true);
            $this->command->info("Created directory: resources/js/{$this->jsDirectory}/globals");
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
        $path = resource_path("js/$file");
        
        if (!File::exists($path)) {
            $file = text(
                label: "Target Js File for dark mode integration.",
                placeholder: 'app.js',
                hint: "File path relative to resources/js directory where Fluxtor assets will be injected."
            );
        }

        $content = File::get($path);

        // Check if import already exists
        if (strpos($content, "import './{$this->jsDirectory}/globals/theme.js'") === false) {
            $importStatement = "import './{$this->jsDirectory}/globals/theme.js'; /* By Fluxtor.dev */ \n\n";
            $content = $importStatement . $content;
        }

        if (strpos($content, "import './{$this->jsDirectory}/utils.js'") === false) {
            $importStatement = "import './{$this->jsDirectory}/utils.js'; /* By Fluxtor.dev */ \n\n";
            $content = $importStatement . $content;
        }

        File::put($path, $content);

        $this->command->info("Added import statement to: {$file}");
    }

}
