<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => 'required|integer|exists:job_applications,id',
            'content' => 'required|string|max:10000',
        ];
    }
}
