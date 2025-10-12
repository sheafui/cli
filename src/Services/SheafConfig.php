<?php

namespace Sheaf\Cli\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SheafConfig
{
    public static function saveLoggedInUserCredentials(string $email, string $token)
    {
        $configFile = self::configFilePath();

        if (File::exists($configFile)) {
            $data = File::get($configFile);
            $data = json_decode($data, true);
        }

        $data['user'] =  [
            'email' => $email,
            'token' => $token,
            'logged_in_at' => now()->toIso8601String(),
        ];

        File::put("$configFile", json_encode($data));
    }

    public static function saveProjectHash()
    {
        $configFile = self::configFilePath();

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

        File::put($configFile, json_encode($data));

        return $projectHash;
    }

    public static function getProjectHash()
    {
        try {
            $configFile = self::configFilePath();

            if (!File::exists($configFile)) {
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

    public static function configFilePath()
    {
        return base_path('sheaf.json');
    }

    public static function getUserToken()
    {
        try {
            $configFile = self::configFilePath();

            if (!File::exists($configFile)) {
                return null;
            }

            $userData = File::get("$configFile");

            return json_decode($userData)['user']['token'];
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function getCurrentUser()
    {
        try {
            $configFile = self::configFilePath();

            if (!File::exists($configFile)) {
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
        $sheafFile = self::getSheafFile();

        $sheafFile['components'][$componentName] = [
            'installationTime' => time()
        ];

        self::saveSheafFile($sheafFile);
    }

    public static function saveSheafFile($sheafFile) {
        File::put(self::configFilePath(), json_encode($sheafFile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function getSheafFile()
    {
        $sheafFile = [];

        if (File::exists(self::configFilePath())) {
            $sheafFile = json_decode(File::get(self::configFilePath()), true);
        }

        return $sheafFile;
    }

    public static function loadSheafLock(): array
    {
        $sheafLockPath = base_path('sheaf-lock.json');

        if (!File::exists($sheafLockPath)) {
            return [];
        }

        return json_decode(File::get($sheafLockPath), true) ?: [];
    }

    public static function saveSheafLock(array $sheafLock): void
    {
        File::put(
            base_path('sheaf-lock.json'),
            json_encode($sheafLock, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
