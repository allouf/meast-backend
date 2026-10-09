<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class SetApplicationRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => 'required|integer|exists:job_applications,id',
            'rating' => 'required|integer|min:1|max:5',
            'rating_note' => 'nullable|string|max:1000',
        ];
    }
}
