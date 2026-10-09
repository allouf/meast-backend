<?php

namespace Modules\Job\Http\Requests\Admin\Job;

use Modules\Job\Enums\EmploymentType;
use Modules\Job\Enums\ExperienceLevel;
use Modules\Job\Enums\JobStatus;
use Modules\Job\Enums\WorkMode;
use Illuminate\Foundation\Http\FormRequest;

class JobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $statusValues = implode(',', array_column(JobStatus::cases(), 'value'));
        $employmentValues = implode(',', array_column(EmploymentType::cases(), 'value'));
        $experienceValues = implode(',', array_column(ExperienceLevel::cases(), 'value'));
        $workModeValues = implode(',', array_column(WorkMode::cases(), 'value'));

        $rules = [
            'title' => 'required|string|max:255',
            'employment_type' => 'required|string|in:' . $employmentValues,
            'experience_level' => 'required|string|in:' . $experienceValues,
            'country_code' => 'required|string|size:2',
            'region' => 'nullable|string|max:255',
            'work_mode' => 'required|string|in:' . $workModeValues,
            'description' => 'required|string',
            'salary_min' => 'nullable|numeric|min:0',
            'salary_max' => 'nullable|numeric|min:0|gte:salary_min',
            'hide_salary' => 'nullable|boolean',
            'application_deadline' => 'nullable|date',
            'status' => 'nullable|string|in:' . $statusValues,
            'application_link' => 'nullable|string|url|max:500',
            'job_template_id' => 'nullable|exists:job_templates,id',
            'employer_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:job_categories,id',
        ];

        if ($this->route('id')) {
            $rules['employer_id'] = 'sometimes|exists:users,id';
        }

        return $rules;
    }
}
