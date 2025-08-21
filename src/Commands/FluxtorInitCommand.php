<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\PackageInitializationService;
use Fluxtor\Cli\Support\InitializationConfig;
use Illuminate\Console\Command;

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
                            {--with-dark-mode       : Include dark mode theme variables and utilities} 
                            {--with-livewire        : Install and setup livewire} 
                            {--with-phosphor        : Install and configure Phosphor Icons package}
                            {--css-file=app.css     : Target CSS file name for package assets injection (relative to resources/css/)}
                            {--theme-file=theme.css : Name for the generated theme CSS file (relative to resources/css/)}
                            {--skip-prompts         : Skip interactive prompts and use default configuration}
                            {--force                : Force overwrite existing files and configurations}';

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

        $initConfig = new InitializationConfig(
            enablePhosphorIcons: $configuration['phosphor_icons'],
            enableDarkMode: $configuration['dark_mode'],
            targetCssFile: $configuration['css_file'],
            themeFileName: $configuration['theme_file'],
            isUseLivewire: $configuration['livewire'],
            forceOverwrite: $this->option('force')
        );

        $packageService = new PackageInitializationService(
            command: $this,
            initConfig: $initConfig
        );

        $this->heading();

        $result = $packageService->initializePackage();

        if ($result) {
            $this->displaySuccess($configuration);
            return Command::SUCCESS;
        }

        $this->error('Fluxtor package initialization failed. Please check the logs for details.');
        return Command::FAILURE;
    }

    /**
     * Gather configuration from options and interactive prompts
     */
    protected function gatherConfiguration(): array
    {
        if ($this->option('skip-prompts')) {
            return $this->getDefaultConfiguration();
        }

        $config = [
            'phosphor_icons' => $this->determinePhosphorIconsInstallation(),
            'livewire' => $this->determineLivewireSetup(),
            'dark_mode' => $this->determineDarkModeSetup(),
            'css_file' => $this->determineCssFileName(),
            'theme_file' => $this->determineThemeFileName(),
        ];


        return $config;
    }

    /**
     * Get default configuration when skipping prompts
     */
    protected function getDefaultConfiguration(): array
    {
        return [
            'phosphor_icons' => $this->option('with-phosphor'),
            'livewire' => $this->option('with-livewire'),
            'dark_mode' => $this->option('with-dark-mode'),
            'css_file' => $this->option('css-file'),
            'theme_file' => $this->option('theme-file'),
        ];
    }

    /**
     * Determine whether to install livewire
     */
    protected function determineLivewireSetup()
    {
        if ($this->option("with-livewire")) {
            return true;
        }

        return confirm(
            label: 'Install and setup livewire?',
            default: true,
            hint: "Choose Yes to enable Livewire support, or No if you only use Alpine."
        );
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
            label: 'Theme CSS file name',
            placeholder: $defaultName,
            default: $defaultName,
            hint: 'Generated Fluxtor theme file.',
            validate: fn($input) => $this->validateCssFileName($input)
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
     * Display the Fluxtor package banner
     */
    protected function  displayBanner(): void
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


    /**
     * Display success message with package configuration summary
     */
    protected function displaySuccess(array $configuration): void
    {
        $this->newLine();
        $this->line(' Fluxtor initialized successfully!');
        $this->newLine();

        $this->line('<fg=green>Package Configuration:</fg=green>');
        $this->line("  • Main CSS file: <fg=yellow>{$configuration['css_file']}</fg=yellow>");
        $this->line("  • Theme file: <fg=yellow>{$configuration['theme_file']}</fg=yellow>");
        if ($configuration['dark_mode']) {
            $this->line("  • JS files: <fg=yellow>utils.js and globals/theme.js</fg=yellow>");
        }
        if ($configuration['livewire']) {
            $this->line("  • Livewire: <fg=yellow>✓ Installed and configured</fg=yellow>");
        } else {
            $this->line("  • Alpine: <fg=yellow>✓ Installed and configured</fg=yellow>");
        }
        $this->line('  • Dark mode support: ' . ($configuration['dark_mode'] ? '<fg=green>✓ Enabled</fg=green>' : '<fg=red>✗ Disabled</fg=red>'));
        $this->line('  • Phosphor Icons: ' . ($configuration['phosphor_icons'] ? '<fg=green>✓ Installed</fg=green>' : '<fg=red>✗ Skipped</fg=red>'));

        $this->newLine();
        $this->line('<fg=green>Package Components Installed:</fg=green>');
        $this->line('  • CSS theme variables and custom properties');
        $this->line('  • Utility classes and component styles');
        $this->line('  • Laravel Blade components integration');
        $this->line('  • Asset compilation configuration');
    }

    private function heading()
    {
        $heading = 'Initializing Fluxtor...';

        $length = strlen("  {$heading}") + 4;

        $this->newLine();
        $this->line(" <fg=green>" . str_repeat("═", $length) . "</fg=green>" );
        $this->line("   <fg=green>" . "  {$heading}" . "</fg=green>" );
        $this->line(" <fg=green>" . str_repeat("═", $length) . "</fg=green>" );
        $this->newLine();
    }
}
