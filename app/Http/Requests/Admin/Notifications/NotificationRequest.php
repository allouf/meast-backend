<?php

namespace App\Http\Requests\Admin\Notifications;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|array',
            'title.en' => 'required|string',
            'title.ar' => 'required|string',
            'body' => 'required|array',
            'body.en' => 'required|string',
            'body.ar' => 'required|string',
            'icon' => 'nullable|image|mimes:png,jpg,jpeg',
            'data' => 'nullable|array',
        ];
    }
}
