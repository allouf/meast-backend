<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployerTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_id' => 'required|exists:job_postings,id',
            'name' => 'required|string|max:64',
            'color' => 'required|string|max:16',
        ];
    }
}
