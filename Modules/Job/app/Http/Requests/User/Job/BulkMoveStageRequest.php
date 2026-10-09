<?php

namespace Modules\Job\Http\Requests\User\Job;

use Modules\Job\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;

class BulkMoveStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = implode(',', ApplicationStatus::values());

        return [
            'application_ids' => 'required|array',
            'application_ids.*' => 'integer|min:1',
            'stage' => 'required|string|in:' . $statusValues,
        ];
    }
}
