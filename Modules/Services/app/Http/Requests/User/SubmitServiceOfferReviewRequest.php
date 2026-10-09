<?php

namespace Modules\Services\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SubmitServiceOfferReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'description' => 'nullable|string|max:500',
        ];
    }
}
