<?php

namespace Sheaf\Cli\Commands;

use Sheaf\Cli\Services\AccountService;
use Sheaf\Cli\Services\SheafConfig;
use Illuminate\Console\Command;

class WhoAmICommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sheaf:whoami';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the currently authenticated Sheaf account information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentUser = SheafConfig::getCurrentUser();

        if(!$currentUser) {
            $this->components->warn("You're not log in. please login first with your sheaf account.");
            return Command::SUCCESS;
        }

        $this->components->info("You're login as {$currentUser['email']}");
    }
}
