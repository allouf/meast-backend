<?php

namespace Modules\Core\Http\Controllers\User\Settings;

use App\Http\Controllers\Controller;
use Modules\Core\Models\General;
use Illuminate\Http\Request;

class SharedController extends Controller
{
    public function getCountries(Request $request)
    {
        return $this->successResponse(__('messages.shared.fetch_success'), ['countries' => [], 'meta' => null]);
    }

    public function getRegionsByCountry($countryId)
    {
        return $this->successResponse(__('messages.shared.fetch_success'), []);
    }

    public function privacyPolicy()
    {
        $locale = app()->getLocale();
        $privacy = General::where('key', "policy_$locale")->first();
        $data = $privacy ? $privacy->value : null;
        return $this->successResponse(__('messages.shared.fetch_success'), ['privacy_policy' => $data]);
    }

    public function policyTerms()
    {
        $locale = app()->getLocale();

        $items = General::where('key', 'like', "%_{$locale}")
            ->get(['key', 'value'])
            ->map(function ($item) use ($locale) {
                return [
                    'key' => str_replace("_$locale", '', $item->key),
                    'value' => $item->value,
                ];
            });

        return $this->successResponse(__('messages.shared.fetch_success'), ['pages' => $items]);
    }

    public function viewPolicyTerms(string $key)
    {
        $locale = app()->getLocale();
        $fullKey = "{$key}_{$locale}";

        $page = General::where('key', $fullKey)->first();

        if (!$page) {
            return $this->errorResponse(__('messages.shared.page_not_found'), 404);
        }

        return $this->successResponse(__('messages.shared.fetch_success'), [
            'key' => $key,
            'value' => $page->value,
        ]);
    }

    public function contactInfo()
    {
        $settings = General::all()
            ->filter(function ($item) {
                return !preg_match('/_(ar|en)$/', $item->key);
            })
            ->mapWithKeys(fn($item) => [$item->key => $item->value]);

        return $this->successResponse(__('messages.shared.fetch_success'), $settings);
    }
}
