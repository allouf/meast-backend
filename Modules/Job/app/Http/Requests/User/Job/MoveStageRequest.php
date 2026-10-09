<?php

namespace Modules\Job\Http\Requests\User\Job;

use Modules\Job\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;

class MoveStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = implode(',', ApplicationStatus::values());

        return [
            'application_id' => 'required|integer|exists:job_applications,id',
            'stage' => 'required|string|in:' . $statusValues,
        ];
    }
}
