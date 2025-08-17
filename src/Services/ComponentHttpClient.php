<?php

namespace Fluxtor\Cli\Services;

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
            return [
                'message' => "Failed to Install the Component, please try again, or contact us.",
                'success' => false
            ];
        }

        if (!$isComponentFree['isFree'] && !$this->token) {
            return [
                'message' => "You need to login, Please run 'php artisan fluxtor:login' and login with your fluxtor account.",
                'success' => false
            ];
        }

        $response =  Http::asJson()
            ->when(!$isComponentFree['isFree'], fn($http) => $http->withToken($this->token))
            ->get("{$this->url}/api/cli/components/$componentName");

        if ($response->failed()) {
            $component = Str::of($componentName)->headline();
            $responseJson = array_key_exists('message', $response->json() ?? []) ? $response->json()['message'] : "";

            return [
                'message' => "Failed to install the component '$component'. \n $responseJson",
                'success' => false
            ];
        }

        return [
            'success' => true,
            'data' => $response->collect()
        ];
    }

    public function isComponentFree(string $componentName)
    {
        return Http::get("{$this->url}/api/cli/components/$componentName/is-free")->collect();
    }
}
