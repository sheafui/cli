<?php


namespace Fluxtor\Cli\Support;
use Illuminate\Support\Str;


class InstallationConfig {

    public function __construct(
        private string $name = '',
        private bool $force = false,
        private bool $skipDeps = false,
        private bool $onlyDeps = false,
        private bool $internalDeps = false,
        private bool $externalDeps = false,
        private bool $isDryRun = false,
        private bool $componentOutdated = false,
        ) {}

    public function componentName() {
        return $this->name;
    }

    public function componentHeadlineName() {
        return Str::of($this->name)->headline();
    }

    public function setComponentName(string $name)
    {
        $this->name = $name;
    }

    public function shouldForceOverwriting() {
        return $this->force;
    }

    public function isComponentOutdated()
    {
        return $this->componentOutdated;
    }

    public function shouldInstallInternalDeps() {
        return $this->internalDeps || $this->onlyDeps;
    }

    public function shouldInstallOnlyDeps() {
        return $this->onlyDeps;
    }

    public function shouldInstallExternalDeps() {
        return $this->externalDeps || $this->onlyDeps;
    }

    public function shouldSkipInstallDeps() {
        return $this->skipDeps;
    }

    public function isDryRun() {
        return $this->isDryRun;
    }

    public function setOnlyDeps(bool $onlyDeps) {
        $this->onlyDeps = $onlyDeps;
    }

    public function setOverwrite()
    {
        $this->force = true;
    }

    public function setComponentState(bool $outDated = false)
    {
        $this->componentOutdated = $outDated;
    }
}