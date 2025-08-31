<?php


namespace Sheaf\Cli\Support;

class InitializationConfig
{

    public function __construct(
        protected bool $enablePhosphorIcons = false,
        protected bool $enableDarkMode = false,
        protected string $themeFileName,
        protected string $targetCssFile,
        protected bool $forceOverwrite = false,
        protected bool $isUseLivewire = false,
    ) {}


    public function shouldInstallPhosphorIcons()
    {
        return $this->enablePhosphorIcons;
    }

    public function shouldEnableDarkMode()
    {
        return $this->enableDarkMode;
    }

    public function getThemeFileName()
    {
        // Ensure file names have proper extensions
        if (!str_ends_with($this->themeFileName, '.css')) {
            $this->themeFileName .= '.css';
        }

        return $this->themeFileName;
    }

    public function getTargetCssFile()
    {

        if (!str_ends_with($this->targetCssFile, '.css')) {
            $this->targetCssFile .= '.css';
        }
        return $this->targetCssFile;
    }

    public function shouldForceOverwrite()
    {
        return $this->forceOverwrite;
    }

    public function shouldSetupLivewire()
    {
        return $this->isUseLivewire;
    }
}
