<?php

namespace Fluxtor\Cli\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class InitService extends Command
{

    public function __construct(protected $phosphorIcon = false, protected $darkMode = false, protected $cssFileName = 'theme')
    {
    }

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

    public function init()
    {
        $this->info("Installing `wireui/heroicons`...");
        Process::run('composer require wireui/heroicons');
        $this->info("`wireui/heroicons` installed.");

        if ($this->phosphorIcon) {
            $this->info("Installing `wireui/phosphoricons`...");
            Process::run('composer require wireui/phosphoricons');
            $this->info("`wireui/phosphoricons` installed.");
        }

        $this->info("*****************************************");
        $this->info("*** Adding the required css variables ***");
        $this->info("*****************************************");

        $this->createSeparateThemeFile($this->cssFileName, $this->darkMode);

        if ($this->darkMode) {
            $this->info("**********************************");
            $this->info("*** Adding the dark Mode files ***");
            $this->info("**********************************");
            // Add dark mode utils.js and theme.js and import them.
        }
    }

    /**
     * Create a separate theme.css file and import it
     */
    protected function createSeparateThemeFile(string $fileName, bool $isDarkMode)
    {
        $themeFile = resource_path("css/{$fileName}.css");
        $appCssFile = resource_path('css/' . $this->option('app'));

        // Create Theme Css file

        $themeContent = $this->themeContent;

        if ($isDarkMode) {
            $themeContent += '\n\n' + $this->darkThemeContent;
        }

        File::put($themeFile, $themeContent);
        $this->info('Created theme file: ' . $themeFile);

        // Add import to main CSS file if it exists
        if (File::exists($appCssFile)) {
            $this->addImportToAppCssFile($appCssFile);
        } else {
            $this->warn("Main CSS file '{$this->option('app')}' not found in resources/css/");
            $this->info("You can manually import the theme by adding this line to your main CSS file:");
            $this->line("@import 'theme.css';");
        }
    }

    /**
     * Add import statement to main CSS file
     */
    protected function addImportToAppCssFile($appCssFile)
    {
        $appCssContent = File::get($appCssFile);

        // Check if import already exists
        if (
            strpos($appCssContent, "@import 'theme.css'") !== false ||
            strpos($appCssContent, '@import "theme.css"') !== false
        ) {
            $this->info('Import statement already exists in main CSS file.');
            return;
        }

        // Add import at the beginning
        $importStatement = "@import 'theme.css'; /* By Fluxtor.dev */ \n\n";
        $newContent = $importStatement . $appCssContent;

        File::put($appCssFile, $newContent);
        $this->info("Added import statement to: {$this->option('app')}");
    }
}
