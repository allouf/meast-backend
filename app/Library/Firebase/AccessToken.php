<?php

namespace App\Library\Firebase;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AccessToken
{
    private static $credentialsFilePath;
    private static $projectId;
    private static $cacheKey = 'firebase_access_token';

    public static function initialize($credentialsFilePath, $projectId)
    {
        self::$credentialsFilePath = $credentialsFilePath;
        self::$projectId = $projectId;
    }

    public static function getToken()
    {
        if (!self::$credentialsFilePath || !self::$projectId) {
            throw new HttpException(500, "AccessToken not initialized. Call initialize() first.");
        }

        $cachedToken = Cache::get(self::$cacheKey);
        if ($cachedToken) {
            return $cachedToken;
        }

        $scope = 'https://www.googleapis.com/auth/firebase.messaging';
        $credentials = new ServiceAccountCredentials($scope, self::$credentialsFilePath);

        try {
            $token = $credentials->fetchAuthToken(HttpHandlerFactory::build());
            $accessToken = $token['access_token'] ?? null;

            if ($accessToken) {
                Cache::put(self::$cacheKey, $accessToken, now()->addMinutes(60));
            }

            return $accessToken;
        } catch (\Exception $e) {
            throw new HttpException(500, "Failed to fetch access token: " . $e->getMessage());
        }
    }
}
