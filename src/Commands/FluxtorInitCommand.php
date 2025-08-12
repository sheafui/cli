<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\InitService;
use Illuminate\Support\Facades\Process;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class FluxtorInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:init 
                            {--with-dark-mode : Include dark mode theme variables and utilities} 
                            {--with-phosphor : Install and configure Phosphor Icons package}
                            {--css-file=app.css : Target CSS file name for package assets injection (relative to resources/css/)}
                            {--theme-file=theme.css : Name for the generated theme CSS file (without extension)}
                            {--skip-prompts : Skip interactive prompts and use default configuration}
                            {--force : Force overwrite existing files and configurations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize Fluxtor package with all required dependencies, assets, and configurations for your Laravel project.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayBanner();

        $configuration = $this->gatherConfiguration();

        $installPhosphorIcons = $this->option('phosphoricons');

        if (!$installPhosphorIcons) {
            $installPhosphorIcons = text(
                label: "Do You want to install `phosphoricons`?",
                placeholder: 'Y/N',
                required: false,
                transform: fn($input) => strtolower($input) === 'y'
            );
        }

        $cssFileName = text(
            label: "what the file name?: ",
            placeholder: "theme",
            required: true,
            default: 'main'
        );

        $darkMode = $this->option('dark');

        if (!$darkMode) {
            $darkMode = text(
                label: "Do you use dark Mode",
                placeholder: "Y/N",
                required: false,
                default: "N",
                transform: fn($input) => strtolower($input) === 'y'
            );
        }

        if (!$darkMode) {
            $darkMode = text(
                label: "Do you want to initialize dark mode?",
                placeholder: "Y/N",
                required: false,
                default: "N",
                transform: fn($input) => strtolower($input) === 'y'
            );
        }

        $initService = new InitService($installPhosphorIcons, $darkMode, $cssFileName);

        $initService->init();
    }

    /**
     * Gather configuration from options and interactive prompts
     */
    protected function gatherConfiguration(): array
    {
        if ($this->option('skip-prompts')) {
            return $this->getDefaultConfiguration();
        }

        return [
            'phosphor_icons' => $this->determinePhosphorIconsInstallation(),
            'dark_mode' => $this->determineDarkModeSetup(),
            'css_file' => $this->determineCssFileName(),
            'theme_file' => $this->determineThemeFileName(),
        ];
    }

    /**
     * Get default configuration when skipping prompts
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'phosphor_icons' => $this->option('with-phosphor'),
            'dark_mode' => $this->option('with-dark-mode'),
            'css_file' => $this->option('css-file'),
            'theme_file' => $this->option('theme-file'),
        ];
    }

    /**
     * Determine whether to install Phosphor Icons
     */
    protected function determinePhosphorIconsInstallation(): bool
    {
        if ($this->option('with-phosphor')) {
            return true;
        }

        return confirm(
            label: 'Install Phosphor Icons package?',
            default: false,
            hint: 'Adds comprehensive icon library with 6000+ icons to your project'
        );
    }

    /**
     * Determine whether to setup dark mode
     */
    protected function determineDarkModeSetup(): bool
    {
        if ($this->option('with-dark-mode')) {
            return true;
        }

        return confirm(
            label: 'Include dark mode theme support?',
            default: true,
            hint: 'Adds CSS custom properties and utilities for dark/light theme switching'
        );
    }

    /**
     * Determine the target CSS file name
     */
    protected function determineCssFileName(): string
    {
        $defaultFile = $this->option('css-file');
        
        return text(
            label: 'Target CSS file for package assets integration',
            placeholder: $defaultFile,
            default: $defaultFile,
            hint: 'File path relative to resources/css/ directory where Fluxtor assets will be injected',
            validate: fn($input) => $this->validateCssFileName($input)
        );
    }

    /**
     * Determine the theme CSS file name
     */
    protected function determineThemeFileName(): string
    {
        $defaultName = $this->option('theme-file');
        
        return text(
            label: 'Theme CSS file name (without extension)',
            placeholder: $defaultName,
            default: $defaultName,
            hint: 'Generated Fluxtor theme file will be saved as {name}.css',
            validate: fn($input) => $this->validateFileName($input)
        );
    }

    /**
     * Validate CSS file name input
     */
    protected function validateCssFileName(?string $input): ?string
    {
        if (empty($input)) {
            return 'CSS file name cannot be empty';
        }

        if (!str_ends_with($input, '.css')) {
            return 'CSS file name must end with .css extension';
        }

        if (!preg_match('/^[a-zA-Z0-9_\/-]+\.css$/', $input)) {
            return 'Invalid CSS file name format';
        }

        return null;
    }

    /**
     * Validate theme file name input
     */
    protected function validateFileName(?string $input): ?string
    {
        if (empty($input)) {
            return 'Theme file name cannot be empty';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
            return 'File name can only contain letters, numbers, hyphens, and underscores';
        }

        if (in_array(strtolower($input), ['index', 'main', 'bootstrap', 'tailwind'])) {
            return 'Please choose a different name to avoid conflicts with common CSS frameworks';
        }

        return null;
    }


    /**
     * Display the Fluxtor package banner
     */
    protected function displayBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=blue>███████╗██╗     ██╗   ██╗██╗  ██╗████████╗ ██████╗ ██████╗ </>');
        $this->line('  <fg=blue>██╔════╝██║     ██║   ██║╚██╗██╔╝╚══██╔══╝██╔═══██╗██╔══██╗</>');
        $this->line('  <fg=blue>█████╗  ██║     ██║   ██║ ╚███╔╝    ██║   ██║   ██║██████╔╝</>');
        $this->line('  <fg=blue>██╔══╝  ██║     ██║   ██║ ██╔██╗    ██║   ██║   ██║██╔══██╗</>');
        $this->line('  <fg=blue>██║     ███████╗╚██████╔╝██╔╝ ██╗   ██║   ╚██████╔╝██║  ██║</>');
        $this->line('  <fg=blue>╚═╝     ╚══════╝ ╚═════╝ ╚═╝  ╚═╝   ╚═╝    ╚═════╝ ╚═╝  ╚═╝</>');
        $this->newLine();
        $this->line('  <fg=gray>Laravel UI Package Initialization & Setup</fg=gray>');
        $this->newLine();
    }
}
