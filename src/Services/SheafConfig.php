<?php

namespace Sheaf\Cli\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SheafConfig
{
    public static function saveLoggedInUserCredentials(string $email, string $token)
    {
        $configFile = self::configFile();

        if(File::exists($configFile)) {
            $data = File::get($configFile);
            $data = json_decode($data, true);
        }

        $data['user'] =  [
            'email' => $email,
            'token' => $token,
            'logged_in_at' => now()->toIso8601String(),
        ];

        File::replace("$configFile", json_encode($data));
    }

    public static function saveProjectHash()
    {
        $configFile = self::configFile();

        $data = [];

        if (File::exists($configFile)) {
            $data = File::get($configFile);
            $data = json_decode($data, true);
        }

        if (array_key_exists('project_hash', $data)) {
            return;
        }

        $projectHash = (string) Str::uuid();
        $data['project_hash'] = $projectHash;

        File::replace($configFile, json_encode($data));

        return $projectHash;
    }

    public static function getProjectHash()
    {
        try {
            $configFile = self::configFile();

            if(!File::exists($configFile)) {
                return self::saveProjectHash();
            }

            $data = File::get("$configFile");

            $projectHash = json_decode($data)['project_hash'];

            if (!$projectHash) {
                $projectHash = self::saveProjectHash();
            }

            return $projectHash;
        } catch (\Throwable $th) {
            return self::saveProjectHash();;
        }
    }

    public static function configFile()
    {
        return base_path('sheaf.json');
    }

    public static function getUserToken()
    {
        try {
            $configFile = self::configFile();

            if(!File::exists($configFile)) {
                return null;
            }

            $userData = File::get("$configFile");

            return json_decode($userData)['user']['token'];
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function getConfigFile()
    {
        try {
            $configFile = self::configFile();

            if(!File::exists($configFile)) {
                return null;
            }

            $userData = File::get("$configFile");

            return json_decode($userData, true)['user'];
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function saveInstalledComponent(string $componentName)
    {
        $installedComponents = self::getInstalledComponents();

        $installedComponents['components'][$componentName] = [
            'installationTime' => time()
        ];

        File::put(base_path('sheaf.json'), json_encode($installedComponents, true));
    }

    public static function getInstalledComponents()
    {
        $installedComponents = [];

        if (File::exists(base_path('sheaf.json'))) {
            $installedComponents = json_decode(File::get(base_path('sheaf.json')), true);
        }

        return $installedComponents;
    }
}
