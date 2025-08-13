<?php

namespace Fluxtor\Cli\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class PackageInitializationService
{

    public function __construct(
        protected Command $command,
        protected $enablePhosphorIcons = false,
        protected $enableDarkMode = false,
        protected $themeFileName,
        protected $targetCssFile,
        protected string|null $jsDirectory,
        protected $forceOverwrite
    ) {}

    /**
     * The CSS theme content to add
     *
     * @var string
     */
    protected $themeContent = '
            /* By Fluxtor.dev */
            @theme inline {
            /* base color variables */

            /* --color-neutral-900: var(--color-neutral-950); */

            /* primary color varibles */
            --color-primary: var(--color-neutral-800);
            --color-primary-content: var(--color-neutral-800);
            --color-primary-fg: var(--color-white);

            /* radius variables */
            --radius-field: 0.25rem;
            --radius-box: 0.5rem;
            }';

    protected $darkThemeContent = '
            @layer theme {
                .dark {
                    --color-primary: var(--color-white);
                    --color-primary-content: var(--color-white);
                    --color-primary-fg:  var(--color-neutral-800);
                }
            }';

    public function initializePackage()
    {
        try {
            $this->command->info("Installing `wireui/heroicons`...");
            Process::run('composer require wireui/heroicons');
            $this->command->info("`wireui/heroicons` installed.");

            if ($this->enablePhosphorIcons) {
                $this->command->info("Installing `wireui/phosphoricons`...");
                Process::run('composer require wireui/phosphoricons');
                $this->command->info("`wireui/phosphoricons` installed.");
            }

            $this->command->info("*****************************************");
            $this->command->info("*** Adding the required css variables ***");
            $this->command->info("*****************************************");

            $this->createCssThemeFile();

            if ($this->enableDarkMode) {
                $this->command->info("**********************************");
                $this->command->info("*** Adding the dark Mode files ***");
                $this->command->info("**********************************");

                $javascriptAssetService = new JavaScriptAssetService($this->command, $this->jsDirectory);

                $javascriptAssetService->createDarkModeAssets();
            }

            return true;
        } catch (\Throwable $th) {
            return false;
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

        $themeContent = $this->themeContent;

        if ($this->enableDarkMode) {
            $themeContent .= "\n\n{$this->darkThemeContent}";
        }

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
            strpos($appCssContent, "@import './theme.css'") !== false
        ) {
            $this->command->info('Import statement already exists in main CSS file.');
            return;
        }

        // Add import at the beginning
        $importStatement = "@import './theme.css'; /* By Fluxtor.dev */ \n\n";
        $newContent = $importStatement . $appCssContent;

        File::put($targetCssFile, $newContent);
        $this->command->info("Added import statement to: {$targetCssFile}");
    }
}
