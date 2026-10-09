<?php

namespace Modules\Core\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\General;

class GeneralController extends Controller
{
    public function getGeneralSettings(Request $request)
    {
        $validated = $request->validate([
            'group' => 'nullable|in:contact,static,system',
        ]);
        $query = General::query();
        if (!empty($validated['group'])) {
            $query->where('group', $validated['group']);
        }
        $settings = $query->get()->map(function ($item) {
            return [
                'key' => $item->key,
                'value' => $item->value,
                'group' => $item->group,
            ];
        });
        return $this->successResponse(__('fetch_success'), $settings);
    }

    public function storeGeneralSetting(Request $request)
    {
        $validated = $request->validate([
            'key'   => 'required|string|unique:generals,key',
            'value' => 'nullable|string',
            'group' => 'required|in:contact,static,system',
        ]);
        $setting = General::create($validated);
        return $this->successResponse(__('create_success'), $setting);
    }

    public function updateGeneralSetting(Request $request, $key)
    {
        $validated = $request->validate([
            'value' => 'nullable|string',
            'group' => 'required|in:contact,static,system',
        ]);
        $setting = General::where('key', $key)->firstOrFail();
        $setting->update($validated);
        return $this->successResponse(__('update_success'), $setting);
    }
}
