<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation pipeline for job application submission (including file upload and template field answers).
     */
    public function rules(): array
    {
        return [
            'job_id' => 'required|exists:job_postings,id',
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240',
            'cover_letter' => 'nullable|string',
            'answers' => 'nullable|array',
            'answers.*.field_id' => 'required_with:answers|integer',
            'answers.*.value' => 'nullable',
        ];
    }
}
