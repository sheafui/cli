<?php

namespace Sheaf\Cli\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Console\Command;

class ContentTemplateService
{
    protected Command $command;
    protected string $stubsPath;

    public function __construct()
    {
        $this->stubsPath = __DIR__ . '/../stubs';
    }

    /**
     * Get content from stub file with variable replacement
     */
    public function getStubContent(string $stubName): string
    {
        $stubPath = $this->stubsPath . "/{$stubName}.stub";

        if (!File::exists($stubPath)) {
            throw new \Exception("Stub file not found: {$stubPath}");
        }

        $content = File::get($stubPath);

        return $content;
    }

    /**
     * Generate theme CSS content
     */
    public function generateThemeCss(bool $includeDarkMode = false): string
    {
        $content = $this->getStubContent('theme.css');

        if ($includeDarkMode) {
            $darkContent = $this->getStubContent('theme-dark.css');
            $content .= "\n\n" . $darkContent;
        }

        return $content;
    }

}
