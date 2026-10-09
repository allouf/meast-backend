<?php

namespace Modules\Advertisement\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class AdvertisementFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'advertisement_id' => 'required|exists:advertisements,id',
        ];
    }

    public function messages(): array
    {
        return [
            'advertisement_id.required' => 'The advertisement ID is required.',
            'advertisement_id.exists' => 'The selected advertisement does not exist.',
        ];
    }
}
