<?php

namespace Sheaf\Cli\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class SheafConfig
{
    public static function saveLoggedInUserCredentials(string $email, string $token)
    {
        $data = [
            'user' => [
                'email' => $email,
                'token' => $token,
                'logged_in_at' => now()->toIso8601String(),
            ],
        ];

        $configDirectory = self::configDirectory();

        File::ensureDirectoryExists($configDirectory);
        File::replace("$configDirectory/config.json", serialize($data));
    }

    public static function configDirectory()
    {
        $home = getenv('HOME') ?: $_SERVER['HOME'] ?? (getenv('USERPROFILE') ?? ($_SERVER['USERPROFILE'] ?? null));

        if (!$home) {
            throw new RuntimeException('Unable to determine user home directory.');
        }
        return rtrim($home, '/') . '/.sheaf';
    }

    public static function getUserToken()
    {
        try {
            $configDirectory = self::configDirectory();

            $userData = File::get("$configDirectory/config.json");

            return unserialize($userData)['user']['token'];
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function getConfigFile()
    {
        try {
            $configDirectory = self::configDirectory();

            if(!$configDirectory) {
                return null;
            }
            
            $userData = File::get("$configDirectory/config.json");

            return unserialize($userData)['user'];
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
