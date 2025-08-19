<?php

namespace Fluxtor\Cli\Services;

use Fluxtor\Cli\Support\InstallationConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;

class DependencyInstaller
{
    public function __construct(protected InstallationConfig $installationConfig, protected Command $command, protected $components) {}

    public function install($dependencies)
    {
        $name = $this->installationConfig->componentHeadlineName();
        if (!$dependencies) {
            return;
        }

        if ($this->installationConfig->shouldSkipInstallDeps()) {
            $this->command->warn(" Skip Installing Dependencies. $name component might not work as expected, run the same command with the `--only-deps` option.");
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
                    components: $this->components,
                    installationConfig: $this->installationConfig
                ))->install($dep);
            }
        }

        $installationMessage = $installedDependencies ? "All $name dependencies installed successfully." : "$name Dependencies are already up to date.";
        $this->command->info(" <fg=white>$installationMessage</fg=white>");
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
        $installedComponents = FluxtorConfig::getInstalledComponents();

        if (!$installedComponents) {
            return true;
        }

        if (!array_key_exists($dependency, $installedComponents['components'])) {
            return true;
        }

        return $installedComponents['components'][$dependency]['installationTime'] < $info['lastModified'];
    }
}
