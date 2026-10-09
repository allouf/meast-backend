<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Admin;
use App\Utilities\GlobalHelper;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Modules\Core\Models\UserDevice;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TokenService
{
    public function generateTokens(Authenticatable $user, ?string $deviceName = null, ?string $ip = null, ?int $deviceId = null, ?string $existingRefreshToken = null): array
    {
        $isAdmin = $user instanceof Admin;
               // Base claims (always included)
               $claims = [
                'id' => $user->id,
                'name' => $user->name,
            ];
        if ($deviceId && !$isAdmin) {
            $accessToken = JWTAuth::customClaims(['device_id' => $deviceId])->fromUser($user);
        } else {
            $accessToken = JWTAuth::fromUser($user);
        }
        if (!$isAdmin) {
            if ($deviceId) {
                $device = $user->devices()->findOrFail($deviceId);
                if ($existingRefreshToken) {
                    $refreshTokenPlain = $existingRefreshToken;
                } else {
                    $refreshTokenPlain = bin2hex(random_bytes(32));
                    $hashedRefreshToken = hash('sha256', $refreshTokenPlain);
                    $device->update(['refresh_token' => $hashedRefreshToken]);
                }
                $device->update([
                    'last_seen' => now(),
                    'ip_address' => $ip ?? $device->ip_address,
                    'device_name' => GlobalHelper::formatDeviceName($deviceName ?? $device->device_name),
                    'is_revoked' => false,
                ]);
            } else {
                $refreshTokenPlain = bin2hex(random_bytes(32));
                $hashedRefreshToken = hash('sha256', $refreshTokenPlain);
                $newDevice = $user->devices()->create([
                    'device_name' => GlobalHelper::formatDeviceName($deviceName),
                    'ip_address' => $ip,
                    'refresh_token' => $hashedRefreshToken,
                    'last_seen' => now(),
                    'is_revoked' => false,
                ]);
                $deviceId = $newDevice->id;
                $claims['device_id'] = $deviceId;
                $accessToken = JWTAuth::customClaims($claims)->fromUser($user);
            }
        } else {
            $refreshTokenPlain = $existingRefreshToken ?? bin2hex(random_bytes(32));
            $hashedRefreshToken = hash('sha256', $refreshTokenPlain);
            Cache::put("admin_refresh_token:{$hashedRefreshToken}", $user->id, now()->addDays(30));
        }
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshTokenPlain,
            'token_type' => 'bearer',
            'expires_in' => 7 * 24 * 60 * 60,
        ];
    }

    public function validateRefreshToken(string $refreshToken): Authenticatable
    {
        $hashed = hash('sha256', $refreshToken);
        $device = UserDevice::where('refresh_token', $hashed)->where('is_revoked', false)->first();
        if (!$device) {
            throw new HttpException(401, 'Device not found or revoked');
        }
        $device->update(['last_seen' => now()]);
        // Load app User with roles so hasRole() works correctly (e.g. on refresh)
        return \Modules\Core\Models\User::with('roles')->findOrFail($device->user_id);
    }

    public function validateAdminRefreshToken(string $refreshToken): Authenticatable
    {
        $hashed = hash('sha256', $refreshToken);
        $adminId = Cache::get("admin_refresh_token:{$hashed}");
        if (!$adminId) {
            throw new HttpException(401, 'Invalid or expired refresh token');
        }
        $admin = Admin::find($adminId);
        if (!$admin) {
            Cache::forget("admin_refresh_token:{$hashed}");
            throw new HttpException(401, 'Admin not found');
        }
        return $admin;
    }

    public function invalidateCurrentToken(): void
    {
        JWTAuth::parseToken()->invalidate();
    }

    public function invalidateAdminRefreshToken(string $refreshToken): void
    {
        Cache::forget("admin_refresh_token:" . hash('sha256', $refreshToken));
    }

    public function invalidateAllTokens(Authenticatable $user): void
    {
        $user->devices()->delete();
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (\Exception $e) {
            throw new HttpException(400, 'No valid access token found to invalidate');
        }
    }
}
