<?php

namespace Sheaf\Cli\Traits;

use Sheaf\Cli\Services\ComponentInstaller;
use Sheaf\Cli\Services\SheafConfig;
use Sheaf\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;

trait CanHandleDependenciesInstallation
{

    protected InstallationConfig $installationConfig;
    protected $consoleComponent;
    protected Command $command;

    public function initConsoleComponent($consoleComponent)
    {
        $this->consoleComponent = $consoleComponent;
    }

    public function initCommand(Command $command)
    {
        $this->command = $command;
    }

    public function initInstallationConfig(InstallationConfig $installationConfig)
    {
        $this->installationConfig = $installationConfig;
    }

    public function installDependencies($dependencies)
    {
        if (!$dependencies) {
            return;
        }

        if (array_key_exists('internal', $dependencies) && $depInternal = Arr::wrap($dependencies['internal'])) {

            $this->installInternalDeps($depInternal);
        }

        if (array_key_exists('external', $dependencies) && $depExternal = Arr::wrap($dependencies['external'])) {

            $this->installExternalDeps($depExternal);
        }
    }

    public function installInternalDeps(array $deps)
    {
        $name = $this->installationConfig->componentHeadlineName();
        $confirmInstall = $this->installationConfig->shouldInstallInternalDeps();

        $isThereOutdatedDependencies = $this->checkOutDatedDependencies($deps);

        if (!$isThereOutdatedDependencies) {
            $this->command->info(" <fg=white>All Component Dependencies are up to date.</fg=white>");
            return;
        }

        if (!$confirmInstall) {
            $this->command->warn(" $name component requires internal dependencies to function properly.");
            $confirmInstall = confirm(label: 'Install required dependencies?', default: true);
        }

        if (!$confirmInstall) {
            return;
        }

        $this->command->info(" <fg=white>↳ Installing $name internal dependencies.</fg=white>");

        $installedDependencies = false;
        foreach ($deps as $dep => $info) {
            if ($this->shouldInstallDependency($dep, $info)) {
                $this->installationConfig->setComponentState(outDated: true);
                $installedDependencies = true;

                $this->installationConfig->setOnlyDeps(false);
                $this->installationConfig->setComponentName($dep);

                (new ComponentInstaller(
                    command: $this->command,
                    components: $this->consoleComponent,
                    installationConfig: $this->installationConfig
                ))->install($dep);
            }
        }

        $installationMessage = $installedDependencies ? "All $name dependencies installed successfully." : "$name Dependencies are already up to date.";
        $this->command->info(" <fg=white>$installationMessage</fg=white>");
    }

    public function checkOutDatedDependencies(array $deps)
    {
        foreach ($deps as $dep => $info) {
            if ($this->shouldInstallDependency($dep, $info)) {
                return true;
            }
        }

        return false;
    }

    public function installExternalDeps(array $deps)
    {
        $name = $this->installationConfig->componentHeadlineName();
        $confirmInstall = $this->installationConfig->shouldInstallExternalDeps();

        if ($confirmInstall) {
            //! command needs to change to component 
            $this->command->warn(" $name component requires external packages to function properly.");
            $confirmInstall = confirm(label: 'Install required external packages?', default: true);
        }

        if (!$confirmInstall) {
            return;
        }

        $this->command->info(" ↳ Installing $name External Dependencies");
        foreach ($deps as $key => $dep) {
            $this->command->info(" <fg=white>Installing</fg=white> <bg=green, fg=white>$key</bg=green,>...");
            Process::run($dep[1]);
        }
    }

    public function shouldInstallDependency($dependency, $info)
    {
        $installedComponents = SheafConfig::getInstalledComponents();

        if (!$installedComponents) {
            return true;
        }

        if (!array_key_exists($dependency, $installedComponents['components'])) {
            return true;
        }

        return $installedComponents['components'][$dependency]['installationTime'] < $info['lastModified'];
    }
}
