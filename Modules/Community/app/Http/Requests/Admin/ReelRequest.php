<?php

namespace Modules\Community\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReelRequest extends FormRequest
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
            'video' => 'sometimes|file|mimes:mp4,mov,avi,wmv,flv,webm|max:102400',
            'caption' => 'nullable|string|max:1000',
            'status' => 'sometimes|required|in:active,inactive,draft',
            'country_code' => 'nullable|string|size:2',
            'region' => 'nullable|string|max:255',
            'is_featured' => 'nullable|boolean',
            'user_id' => 'nullable|exists:users,id'
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'video.file' => 'The video must be a file.',
            'video.mimes' => 'The video must be a file of type: mp4, mov, avi, wmv, flv, webm.',
            'video.max' => 'The video size cannot exceed 100MB.',
            'caption.max' => 'The caption cannot exceed 1000 characters.',
            'status.required' => 'The status is required.',
            'status.in' => 'The status must be one of: active, inactive, draft.',
            'is_featured.boolean' => 'The featured field must be true or false.',
            'user_id.exists' => 'The selected user does not exist.'
        ];
    }
}
