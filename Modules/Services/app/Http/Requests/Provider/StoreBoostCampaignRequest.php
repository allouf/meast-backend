<?php

namespace Modules\Services\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreBoostCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Pricing, placement, and targeting are resolved server-side; payment is outside this endpoint.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_offer_id' => ['required', 'integer', 'exists:service_offers,id'],
            'boost_plan_id' => ['required', 'integer', 'exists:boost_plans,id'],
        ];
    }
}
