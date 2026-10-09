<?php

namespace Modules\Community\Http\Requests\Admin;

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
            'content' => 'sometimes|required|string|max:10000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'sometimes|in:active,inactive,draft',
            'is_featured' => 'sometimes|boolean',
            'user_id' => 'sometimes|exists:users,id'
        ];

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
            'is_featured.boolean' => 'The featured field must be true or false.',
            'user_id.exists' => 'The selected user does not exist.'
        ];
    }
}
