<?php

namespace Fluxtor\Cli\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ComponentHttpClient
{
    protected string $url;
    protected string|null $token;

    public function __construct()
    {
        $this->url = config('fluxtor.cli.server_url');
        $this->token = FluxtorConfig::getUserToken();
    }
    public function fetchResources(string $componentName)
    {
        $isComponentFree = $this->isComponentFree($componentName);

        if (!$isComponentFree['success']) {
            throw new Exception($isComponentFree['message']);
        }

        if (!$isComponentFree['isFree'] && !$this->token) {
            throw new Exception("You need to login, Please run 'php artisan fluxtor:login' and login with your fluxtor account.");
        }

        $response =  Http::asJson()
            ->when(!$isComponentFree['isFree'], fn($http) => $http->withToken($this->token))
            ->get("{$this->url}/api/cli/components/$componentName");

        if ($response->failed()) {
            $component = Str::of($componentName)->headline();
            $message = array_key_exists('message', $response->json() ?? []) ? $response->json()['message'] : "";

            throw new Exception("Failed to install the component '$component'. \n $message");
        }

        return [
            'success' => true,
            'data' => $response->collect()
        ];
    }

    public function isComponentFree(string $componentName)
    {
        $response = Http::get("{$this->url}/api/cli/components/$componentName/is-free");

        if ($response->failed()) {
            $message = array_key_exists('message', $response->json() ?? []) ? $response->json()['message'] : "";
            throw new Exception("Failed to get the data. {$message}");
        }

        return $response->collect();
    }
}
