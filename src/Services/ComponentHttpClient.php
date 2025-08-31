<?php

namespace Sheaf\Cli\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ComponentHttpClient
{
    protected string $url;
    protected string|null $token;

    public function __construct()
    {
        $this->url = config('sheaf.cli.server_url');
        $this->token = SheafConfig::getUserToken();
    }
    public function fetchResources(string $componentName)
    {

        $response =  Http::asJson()
            ->when($this->token, fn($http) => $http->withToken($this->token))
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

}
