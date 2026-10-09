<?php

namespace Modules\Job\Http\Requests\User\Job;

use Modules\Job\Enums\JobStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = implode(',', array_column(JobStatus::cases(), 'value'));

        return [
            'status' => 'required|string|in:' . $statusValues,
        ];
    }
}
