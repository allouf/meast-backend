<?php

namespace Modules\Community\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoryRequest extends FormRequest
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
            'content' => 'nullable|string|max:1000',
            'media' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,mp4,mov,avi|max:10240', // 10MB max for stories
            'status' => 'nullable|in:active,inactive,draft',
            'background_color' => 'nullable',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'type' => 'required|in:image,video,text',
            'expires_at' => 'nullable|date|after:now',
            'is_featured' => 'nullable|boolean'
        ];

        // For update requests, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['content'] = 'sometimes|nullable|string|max:1000';
            $rules['type'] = 'sometimes|required|in:image,video,text';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.max' => 'The story content cannot exceed 1000 characters.',
            'media.file' => 'The file must be a valid file.',
            'media.mimes' => 'The media must be a file of type: jpeg, png, jpg, gif, webp, mp4, mov, avi.',
            'media.max' => 'The media size cannot exceed 10MB.',
            'status.in' => 'The status must be one of: active, inactive, draft.',
            'type.required' => 'The story type is required.',
            'type.in' => 'The story type must be one of: image, video, text.',
            'expires_at.date' => 'The expiration date must be a valid date.',
            'expires_at.after' => 'The expiration date must be in the future.',
            'is_featured.boolean' => 'The featured field must be true or false.'
        ];
    }
}
