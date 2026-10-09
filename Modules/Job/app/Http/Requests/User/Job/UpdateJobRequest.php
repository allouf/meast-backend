<?php

namespace Modules\Job\Http\Requests\User\Job;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Job\Enums\EmploymentType;
use Modules\Job\Enums\ExperienceLevel;
use Modules\Job\Enums\JobStatus;
use Modules\Job\Enums\WorkMode;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employmentValues = implode(',', array_column(EmploymentType::cases(), 'value'));
        $experienceValues = implode(',', array_column(ExperienceLevel::cases(), 'value'));
        $workModeValues = implode(',', array_column(WorkMode::cases(), 'value'));
        $statusValues = implode(',', array_column(JobStatus::cases(), 'value'));

        return [
            'title' => 'sometimes|required|string|max:255',
            'employment_type' => 'sometimes|required|string|in:' . $employmentValues,
            'experience_level' => 'sometimes|required|string|in:' . $experienceValues,
            'country_code' => 'sometimes|required|string|size:2',
            'region' => 'nullable|string|max:255',
            'work_mode' => 'sometimes|required|string|in:' . $workModeValues,
            'description' => 'sometimes|required|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'currency' => [
                'sometimes',
                'nullable',
                'required_with:salary_min,salary_max',
                'string',
                'size:3',
            ],
            'hide_salary' => 'nullable|boolean',
            'application_deadline' => 'nullable|date',
            'status' => 'sometimes|string|in:' . $statusValues,
            'application_link' => 'nullable|string|url|max:500',
            'job_template_id' => 'nullable|exists:job_templates,id',
            'category_id' => 'sometimes|required|exists:job_categories,id',
        ];
    }
    protected function prepareForValidation(): void
    {
        if ($this->currency) {
            $this->merge([
                'currency' => strtoupper($this->currency),
            ]);
        }
    }
}
