<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Modules\Core\Models\UserDevice;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class ValidateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $deviceId = $payload->get('device_id');
            if ($deviceId) {
                $device = UserDevice::find($deviceId);
                if (!$device) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Device has been revoked. Please login again.',
                    ], 401);
                }
            }
        } catch (Exception $e) {
        }
        return $next($request);
    }
}
