<?php


namespace Fluxtor\Cli\Support;

class InstallationConfig {

    public function __construct(
        private bool $force = false,
        private bool $internalDeps = false,
        private bool $externalDeps = false,
        private bool $isDryRun = false,
        ) {}

    public function shouldForceOverwriting() {
        return $this->force;
    }

    public function shouldInstallInternalDeps() {
        return $this->internalDeps;
    }

    public function shouldInstallExternalDeps() {
        return $this->externalDeps;
    }

    public function isDryRun() {
        return $this->isDryRun;
    }
}