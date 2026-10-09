<?php

namespace Modules\Job\Http\Resources\Admin\Job;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'hide_salary' => $this->hide_salary,
            'application_deadline' => $this->application_deadline?->format('Y-m-d'),
            'expires_at' => $this->application_deadline?->format('Y-m-d'),
            'expires_in_days' => $this->expiresInDays(),
            'status' => $this->status?->value ?? $this->status,
            'application_link' => $this->application_link,
            'job_template_id' => $this->job_template_id,
            'employer_id' => $this->employer_id,
            'category_id' => $this->category_id,
            'employer' => $this->whenLoaded('employer', fn () => $this->employer ? [
                'id' => $this->employer->id,
                'name' => $this->employer->name ?? null,
                'email' => $this->employer->email ?? null,
            ] : null),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'job_template' => $this->whenLoaded('jobTemplate', fn () => $this->jobTemplate ? [
                'id' => $this->jobTemplate->id,
                'title' => $this->jobTemplate->title,
            ] : null),
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
