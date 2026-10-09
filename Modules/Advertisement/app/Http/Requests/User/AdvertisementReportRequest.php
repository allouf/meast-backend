<?php

namespace Modules\Advertisement\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdvertisementReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'advertisement_id' => 'required|exists:advertisements,id',
            'reason' => ['required', 'string', Rule::in(['spam', 'fake', 'inappropriate', 'scam', 'misleading', 'duplicate', 'other'])],
            'description' => 'nullable|string|max:1000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'advertisement_id.required' => 'The advertisement ID is required.',
            'advertisement_id.exists' => 'The selected advertisement does not exist.',
            'reason.required' => 'The reason for reporting is required.',
            'reason.in' => 'The reason must be one of: spam, fake, inappropriate, scam, misleading, duplicate, other.',
            'description.max' => 'The description cannot exceed 1000 characters.',
            'images.array' => 'Images must be an array.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.mimes' => 'Each image must be a file of type: jpeg, png, jpg, gif, webp.',
            'images.*.max' => 'Each image size cannot exceed 2MB.',
        ];
    }
}
