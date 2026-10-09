<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Modules\Core\Models\UserDevice;
use App\Traits\PaginateTrait;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    use PaginateTrait;

    public function listAllDevices(Request $request)
    {
        $query = UserDevice::with('user:id,name,email')
            ->select(['id', 'user_id', 'device_name', 'ip_address', 'last_seen', 'is_revoked', 'created_at']);

        return $this->successResponse(__('messages.admin.devices_fetched'), $this->paginate($query, $request->input('per_page')));
    }

    public function getDevicesByUser(Request $request, $userId)
    {
        $query = UserDevice::where('user_id', $userId)
            ->with('user:id,name,email')
            ->select(['id', 'user_id', 'device_name', 'ip_address', 'last_seen', 'is_revoked', 'created_at']);

        return $this->successResponse(__('messages.admin.user_devices_fetched_successfully'), $this->paginate($query, $request->input('per_page')));
    }

    public function revokeDevice($id)
    {
        $device = UserDevice::findOrFail($id);
        $device->update(['is_revoked' => true]);
        return $this->successResponse(__('messages.admin.device_revoked_successfully'));
    }

    public function revokeAllDevicesForUser($userId)
    {
        $count = UserDevice::where('user_id', $userId)->update(['is_revoked' => true]);

        if ($count === 0) {
            return $this->successResponse(__('messages.admin.no_active_devices_found'));
        }

        return $this->successResponse(__('messages.admin.devices_revoked_for_user', ['count' => $count]));
    }
}
