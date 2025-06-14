<?php

namespace Fluxtor\Cli\Services;

use Illuminate\Support\Facades\File;

class FluxtorConfig
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

    private static function configDirectory() {
        return rtrim(getenv('HOME') ?: $_SERVER['HOME'], '/') . '/.fluxtor';
    }


    public static function getUserToken() {
        try {
            $configDirectory = self::configDirectory();

        $userData = File::get("$configDirectory/config.json");

        return unserialize($userData)['user']['token'];
        } catch (\Throwable $th) {
            return null;
        }
    }

}
