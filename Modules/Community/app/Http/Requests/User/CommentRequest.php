<?php

namespace Modules\Community\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'content' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
            'status' => 'nullable|in:active,inactive,hidden'
        ];

        // For update requests, make some fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['content'] = 'sometimes|required|string|max:1000';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'content.required' => 'The comment content is required.',
            'content.max' => 'The comment content cannot exceed 1000 characters.',
            'parent_id.exists' => 'The selected parent comment does not exist.',
            'status.in' => 'The status must be one of: active, inactive, hidden.'
        ];
    }
}
