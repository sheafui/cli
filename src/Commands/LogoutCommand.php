<?php

namespace Sheaf\Cli\Commands;

use Illuminate\Console\Command;
use Sheaf\Cli\Services\SheafConfig;

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
        $configFile = SheafConfig::configFilePath();

        if (! file_exists($configFile)) {
            $this->components->warn('You are not logged in.');

            return;
        }

        $config = json_decode(file_get_contents($configFile));

        if (empty($config->user)) {
            $this->components->warn('You are not logged in.');

            return;
        }

        unset($config->user);

        file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->components->info('You have logged out.');
    }
}
