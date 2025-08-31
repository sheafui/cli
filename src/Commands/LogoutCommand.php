<?php

namespace Sheaf\Cli\Commands;

use Sheaf\Cli\Services\SheafConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogoutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheaf:logout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Logout from your Sheaf Account.';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $configDirectory = SheafConfig::configDirectory();
        $configFile = "$configDirectory/config.json";
        if (File::exists($configFile)) {
            File::delete($configFile);
            $this->components->info("You have logged out.");
        }
    }
}
