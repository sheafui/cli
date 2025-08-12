<?php

namespace Fluxtor\Cli\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

use function Laravel\Prompts\text;

class JavaScriptAssetService
{
    protected Command $command;
    protected string $jsDirectory;
    protected bool $forceOverwrite;

    public function __construct(Command $command, string $jsDirectory, bool $forceOverwrite = false)
    {
        $this->command = $command;
        $this->jsDirectory = $jsDirectory;
        $this->forceOverwrite = $forceOverwrite;
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
            $globalsCreated = $this->ensureGlobalsDirectory();

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
     * Create utils.js file
     */
    protected function createUtilsFile(): bool
    {
        $filePath = resource_path("js/{$this->jsDirectory}/utils.js");

        if (File::exists($filePath) && !$this->forceOverwrite) {
            $this->command->warn("File already exists: utils.js");

            if (!$this->command->confirm('Overwrite existing utils.js file?', false)) {
                return false;
            }
        }

        $content = $this->getUtilsTemplate();
        File::put($filePath, $content);

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

        $content = $this->getThemeTemplate();
        File::put($filePath, $content);

        $this->command->info("✓ Created: resources/js/{$this->jsDirectory}/globals/theme.js");
        return true;
    }

    /**
     * Ensure globals directory exists (for organization)
     */
    protected function ensureGlobalsDirectory(): bool
    {
        return File::exists(resource_path("js/{$this->jsDirectory}/globals"));
    }

    /**
     * Get utils.js template content
     */
    protected function getUtilsTemplate(): string
    {
        return "
        /**
         * Fluxtor Utility Functions
         * Provides reactive magic property registration for Alpine.js
         */

        export default function defineReactiveMagicProperty(name, rawObject) {
            const instance = Alpine.reactive(rawObject);

            /** 
             * Reactive objects are plain proxies and do not support hooks like stores,
             * or scopes in Alpine.js so we initialize manually 
             */
            if (typeof instance.init === 'function') {
                instance.init();
            }

            Alpine.magic(name, () => instance);
            
            // Register the magic property globally
            // Ex: if the magic is called \$theme, we register Theme into the window
            window[name[0].toUpperCase() + name.slice(1)] = instance;
        }
        ";
    }

    /**
     * Get theme.js template content
     */
    protected function getThemeTemplate(): string
    {
        return "
        /**
         * Fluxtor Dark Mode Theme System
         * Provides comprehensive theme management with Alpine.js integration
         */

        import defineReactiveMagicProperty from '../utils.js';

        document.addEventListener('alpine:init', () => {
            defineReactiveMagicProperty('theme', {
                currentTheme: null,
                storedTheme: null,

                init() {
                    // Check localStorage for stored theme preference
                    this.storedTheme = localStorage.getItem('theme') ?? 'system';

                    // Resolve the configured theme to be only [light, dark]
                    this.currentTheme = computeTheme(this.storedTheme);
                    
                    // Apply initial theme to DOM
                    applyTheme(this.currentTheme);

                    // Listen for system theme changes
                    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
                    mediaQuery.addEventListener('change', (event) => {
                        if (this.storedTheme === 'system') {
                            this.currentTheme = event.matches ? 'dark' : 'light';
                            applyTheme(this.currentTheme);
                        }
                    });
                },

                /**
                 * Set theme preference and persist to localStorage
                 */
                setTheme(newTheme) {
                    this.storedTheme = newTheme;
                    localStorage.setItem('theme', newTheme);
                    
                    this.currentTheme = computeTheme(newTheme);
                    applyTheme(this.currentTheme);
                },

                /**
                 * Theme setter methods
                 */
                setLight() {
                    this.setTheme('light');
                },

                setDark() {
                    this.setTheme('dark');
                },

                setSystem() {
                    this.setTheme('system');
                },

                /**
                 * Toggle between light and dark themes
                 */
                toggle() {
                    if (this.storedTheme === 'system') {
                        // If system, toggle to opposite of current computed theme
                        this.setTheme(this.currentTheme === 'dark' ? 'light' : 'dark');
                    } else {
                        // Toggle between light and dark
                        this.setTheme(this.storedTheme === 'dark' ? 'light' : 'dark');
                    }
                },

                /**
                 * Get current theme state information
                 */
                get() {
                    return {
                        stored: this.storedTheme,
                        current: this.currentTheme,
                        isLight: this.isLight,
                        isDark: this.isDark,
                        isSystem: this.isSystem
                    };
                },

                // Getter methods for easy template usage
                get isLight() {
                    return this.storedTheme === 'light';
                },

                get isDark() {
                    return this.storedTheme === 'dark';
                },

                get isSystem() {
                    return this.storedTheme === 'system';
                },

                /**
                 * Sometimes we need to show only light or dark, not system mode.
                 * These getters handle scenarios where we need the resolved theme state.
                 */
                get isResolvedToLight() {
                    if (this.isSystem) {
                        return getSystemTheme() === 'light';
                    }
                    return this.isLight;
                },

                get isResolvedToDark() {
                    if (this.isSystem) {
                        return getSystemTheme() === 'dark';
                    }
                    return this.isDark;
                }
            });
        });

        /**
         * Static helper functions
         */

        function computeTheme(themePreference) {
            if (themePreference === 'system') {
                return getSystemTheme();
            }
            return themePreference;
        }

        function getSystemTheme() {
            return window.matchMedia('(prefers-color-scheme: dark)').matches 
                ? 'dark' 
                : 'light';
        }

        function applyTheme(theme) {
            const documentElement = document.documentElement;
            
            if (theme === 'dark') {
                documentElement.classList.add('dark');
            } else {
                documentElement.classList.remove('dark');
            }
            
            // Dispatch custom event for theme change listeners
            document.dispatchEvent(new CustomEvent('theme-changed', {
                detail: { theme }
            }));
        }";
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
        $appJsFile = 'app.js';
        if (!File::exists(resource_path('js/app.js'))) {
            $appJsFile = text(
                label: "Target Js File for dark mode integration.",
                placeholder: 'app.js',
                hint: "File path relative to resources/js directory where Fluxtor assets will be injected."
            );
        }

        $appJsContent = File::get(resource_path("js/$appJsFile"));

        // Check if import already exists
        if (
            strpos($appJsContent, "@import '$appJsFile'") !== false ||
            strpos($appJsContent, "@import '$appJsFile'") !== false
        ) {
            $this->command->info('Import statement already exists in main CSS file.');
            return;
        }

        // Add import at the beginning
        $importStatement = "@import '$appJsFile'; /* By Fluxtor.dev */ \n\n";
        $newContent = $importStatement . $appJsContent;

        File::put(resource_path("js/$appJsFile"), $newContent);
        $this->command->info("Added import statement to: {$appJsFile}");
    }

    /**
     * Check if JavaScript files already exist
     */
    public function assetsExist(): array
    {
        return [
            'utils' => File::exists(resource_path("js/{$this->jsDirectory}/utils.js")),
            'theme' => File::exists(resource_path("js/{$this->jsDirectory}/globals/theme.js")),
        ];
    }

    /**
     * Get the relative path for JavaScript files
     */
    public function getJsPath(): string
    {
        return "resources/js/{$this->jsDirectory}";
    }
}
