<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\AccountService;
use Fluxtor\Cli\Services\FluxtorConfig;
use Illuminate\Console\Command;

class WhoAmICommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:whoami';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the currently authenticated Fluxtor account information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentUser = FluxtorConfig::getConfigFile();

        if(!$currentUser) {
            $this->components->warn("You're not log in. please login first with your fluxtor account.");
            return Command::SUCCESS;
        }

        $this->components->info("You're login as {$currentUser['email']}");
    }
}
