<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\FluxtorConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogoutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:logout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Logout from your Fluxtor Account.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $configDirectory = FluxtorConfig::configDirectory();
        $configFile = "$configDirectory/config.json";
        if (File::exists($configFile)) {
            File::delete($configFile);
            $this->components->info("You have logged out.");
        }
    }
}
