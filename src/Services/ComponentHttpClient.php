<?php

namespace Sheaf\Cli\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ComponentHttpClient
{
    protected string $baseUrl;
    protected string|null $token;

    public function __construct()
    {
        $this->baseUrl = config('sheaf.cli.server_url');
        $this->token = SheafConfig::getUserToken();
    }
    public function fetchResources(string $componentName)
    {

        $projectHash = SheafConfig::getProjectHash();

        $url = "{$this->baseUrl}/api/cli/components/$componentName?project_hash=$projectHash";

        $response =  Http::asJson()
            ->when($this->token, fn($http) => $http->withToken($this->token))
            ->get($url);

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
