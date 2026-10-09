<?php

namespace Modules\Core\Http\Controllers\User\Account;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Modules\Core\Models\UserDevice;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class DeviceController extends Controller
{
    public function listMyDevices(Request $request)
    {
        $user = $request->user();
        $currentRefreshToken = $request->input('refresh_token');
        $devices = UserDevice::where('user_id', $user->id)
            ->select(['id', 'device_name', 'ip_address', 'last_seen', 'is_revoked', 'refresh_token', 'created_at'])
            ->get()
            ->map(function ($device) use ($currentRefreshToken) {
                return [
                    'id' => $device->id,
                    'device_name' => $device->device_name,
                    'ip_address' => $device->ip_address,
                    'last_seen' => $device->last_seen?->toDateTimeString(),
                    'is_revoked' => $device->is_revoked,
                    'is_current' => $device->refresh_token === hash('sha256', $currentRefreshToken),
                    'created_at' => $device->created_at?->toDateTimeString(),
                ];
            });
        return $this->successResponse('Your devices fetched successfully', $devices);
    }

    public function revokeDevice($id, Request $request)
    {
        $user = $request->user();
        $device = UserDevice::where('id', $id)->where('user_id', $user->id)->first();
        if (!$device) {
            return $this->errorResponse('Device not found', 404);
        }
        $deviceId = $device->id;
        $isCurrentDevice = false;
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $tokenDeviceId = $payload->get('device_id');
            $isCurrentDevice = $tokenDeviceId && (int) $tokenDeviceId === (int) $deviceId;
        } catch (Exception $e) {
        }
        $device->delete();
        if ($isCurrentDevice) {
            try {
                JWTAuth::parseToken()->invalidate();
            } catch (Exception $e) {
            }
        }
        return $this->successResponse('Device revoked successfully');
    }

    public function revokeAllDevices(Request $request)
    {
        $user = $request->user();
        $count = UserDevice::where('user_id', $user->id)->delete();
        try {
            JWTAuth::parseToken()->invalidate();
        } catch (Exception $e) {
        }
        if ($count === 0) {
            return $this->successResponse('No active devices found for your account');
        }
        return $this->successResponse("$count device(s) revoked successfully");
    }
}
