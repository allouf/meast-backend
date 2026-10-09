<?php

namespace Modules\Job\Http\Resources\User\Job;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

class JobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'employment_type' => $this->employment_type?->value ?? $this->employment_type,
            'experience_level' => $this->experience_level?->value ?? $this->experience_level,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'work_mode' => $this->work_mode?->value ?? $this->work_mode,
            'description' => $this->description,
            'salary_min' => $this->when(! $this->hide_salary, $this->salary_min),
            'salary_max' => $this->when(! $this->hide_salary, $this->salary_max),
            'currency' => $this->when(! $this->hide_salary, $this->currency),
            'hide_salary' => $this->hide_salary,
            'application_deadline' => $this->application_deadline?->format('Y-m-d'),
            'expires_at' => $this->application_deadline?->format('Y-m-d'),
            'expires_in_days' => $this->expiresInDays(),
            'status' => $this->status?->value ?? $this->status,
            'application_link' => $this->application_link,
            'job_template_id' => $this->job_template_id,
            'employer_id' => $this->employer_id,
            'category_id' => $this->category_id,
            'job_template' => $this->job_template_id && $this->jobTemplate
                ? new JobTemplateResource($this->jobTemplate)
                : null,
            'category' => $this->whenLoaded('category', fn() => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),

            'employer' => $this->whenLoaded('employer', function () {
                $company = $this->employer->relationLoaded('company') ? $this->employer->company : null;
                return [
                    'id' => $this->employer->id,
                    'name' => $this->employer->name,
                    'email' => $this->employer->email,
                    'image' => $this->employer->photo ? FileManager::getUrl($this->employer->photo) : null,
                    'company_name' => $company?->company_name ?? $this->employer->name,
                    'company_logo' => $company?->logo ? FileManager::getUrl($company->logo) : ($this->employer->photo ? FileManager::getUrl($this->employer->photo) : null),
                ];
            }),

            'latest_applications' => JobApplicationResource::collection(
                $this->whenLoaded('latestApplications')
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Days until expiration (application_deadline). Positive = in future, 0 = today, negative = past. Null if no deadline.
     */
    protected function expiresInDays(): ?int
    {
        if (! $this->application_deadline) {
            return null;
        }
        $deadline = $this->application_deadline instanceof Carbon
            ? $this->application_deadline
            : Carbon::parse($this->application_deadline);

        return (int) now()->startOfDay()->diffInDays($deadline->startOfDay(), false);
    }
}
