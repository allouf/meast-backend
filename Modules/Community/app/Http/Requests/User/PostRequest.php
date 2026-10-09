<?php

namespace Modules\Community\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'content' => 'required|string|max:10000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'nullable|in:active,inactive,draft',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean'
        ];

        // For update requests, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['content'] = 'sometimes|required|string|max:10000';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The post content is required.',
            'content.max' => 'The post content cannot exceed 10000 characters.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'The image must be a file of type: jpeg, png, jpg, gif, webp.',
            'image.max' => 'The image size cannot exceed 2MB.',
            'status.in' => 'The status must be one of: active, inactive, draft.',
            'is_featured.boolean' => 'The featured field must be true or false.'
        ];
    }
}
