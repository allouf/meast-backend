<?php

namespace Modules\Community\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\BaseResponse;
use Exception;
use Illuminate\Http\Request;
use Modules\Community\Models\CommunitySetting;
use Modules\Community\Models\SuggestedContentSetting;

class SettingController extends Controller
{
    use BaseResponse;

    public function getCommunitySetting()
    {
        try {
            $setting = CommunitySetting::singleton();
            return $this->successResponse('Community setting retrieved', $setting);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'Failed to get community setting');
        }
    }

    public function storeOrUpdateCommunitySetting(Request $request)
    {
        try {
            $validated = $request->validate([
                'community_status'         => 'required|boolean',
                'max_post_length'          => 'required|integer|min:1',
                'reels_duration_seconds'   => 'required|integer|min:1',
                'interaction_window_hours' => 'required|integer|min:1',
            ]);

            $setting = CommunitySetting::singleton();
            $setting->update($validated);

            return $this->successResponse('Community setting saved successfully', $setting);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'Failed to save community setting');
        }
    }

    public function getSuggestedContentSettings()
    {
        try {
            $settings = SuggestedContentSetting::orderBy('priority')->get();
            return $this->successResponse('Suggested content settings retrieved', $settings);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'Failed to get suggested content settings');
        }
    }

    public function storeOrUpdateSuggestedContentSetting(Request $request)
    {
        try {
            $data = $request->all();
            $results = [];

            if (isset($data[0]) && is_array($data[0])) {
                foreach ($data as $item) {
                    $validated = validator($item, [
                        'content_type' => 'required|in:' . implode(',', SuggestedContentSetting::TYPES),
                        'percentage'   => 'required|integer|min:0|max:100',
                        'priority'     => 'required|integer|min:1',
                    ])->validate();

                    $results[] = SuggestedContentSetting::updateOrCreate(
                        ['content_type' => $validated['content_type']],
                        $validated
                    );
                }

                return $this->successResponse('Suggested content settings saved successfully', $results);
            }

            $validated = $request->validate([
                'content_type' => 'required|in:' . implode(',', SuggestedContentSetting::TYPES),
                'percentage'   => 'required|integer|min:0|max:100',
                'priority'     => 'required|integer|min:1',
            ]);

            $setting = SuggestedContentSetting::updateOrCreate(
                ['content_type' => $validated['content_type']],
                $validated
            );

            return $this->successResponse('Suggested content setting saved successfully', $setting);
        } catch (Exception $e) {
            return $this->exceptionResponse($e, 'Failed to save suggested content setting');
        }
    }
}
