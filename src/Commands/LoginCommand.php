<?php

namespace Fluxtor\Cli\Commands;

use Fluxtor\Cli\Services\FluxtorConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class LoginCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fluxtor:login';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Login with your Fluxtor Account.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = text(label: "Email: ", placeholder: 'someone@fluxtor.dev', required: true);
        $password = password(label: "password: ", placeholder: '*********', required: true);

        $serverUrl = config('fluxtor.cli.server_url');

        $result = Http::post("$serverUrl/api/cli/login", ['email'=>$email, 'password' => $password])->onError(function ($response) {
            if($response->failed()) {
                $this->components->error($response->collect()->get('message'));
                exit;
            }
        })->collect();

        $token = $result->get('token');

        FluxtorConfig::saveLoggedInUserCredentials($email, $token);
        
        $this->components->info("Your Have logged in as $email.");
    }
}
