<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Job\Models\Job;
use App\Utilities\FileManager;

class JobApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'status' => $this->status->value,
            'rejection_reason' => $this->when($this->status->value === 'REJECTED', $this->rejection_reason),
            'notes' => JobApplicationNoteResource::collection($this->whenLoaded('notes')),
            'rating' => $this->rating,
            'job' => $this->whenLoaded('job', fn() => [
                'id' => $this->job->id,
                'title' => $this->job->title,
                'description' => $this->job->description,
                'location' => $this->formatJobLocation($this->job),
                'employment_type' => $this->job->employment_type?->value,
                'salary_range' => $this->job->hide_salary ? null : [
                    'min' => $this->job->salary_min !== null ? (float) $this->job->salary_min : null,
                    'max' => $this->job->salary_max !== null ? (float) $this->job->salary_max : null,
                ],
                'work_mood' => $this->job->work_mode?->value,
                'category' => $this->job->relationLoaded('category') && $this->job->category ? [
                    'id' => $this->job->category->id,
                    'name' => $this->job->category->name,
                ] : null,
                'employer' => $this->job->relationLoaded('employer') && $this->job->employer ? [
                    'id' => $this->job->employer->id,
                    'name' => $this->job->employer->name,
                    'logo' => isset($this->job->employer->logo) && $this->job->employer->logo
                        ? FileManager::getUrl($this->job->employer->logo)
                        : (isset($this->job->employer->photo) && $this->job->employer->photo ? FileManager::getUrl($this->job->employer->photo) : null),
                ] : null,
            ]),
            'field_answers' => $this->whenLoaded('fieldAnswers', function () {
                return $this->fieldAnswers->map(function ($answer) {
                    $field = $answer->relationLoaded('jobTemplateField') ? $answer->jobTemplateField : null;
                    $value = $answer->value;
                    if ($value !== null && (str_starts_with((string) $value, '[') || str_starts_with((string) $value, '{'))) {
                        $decoded = json_decode($value, true);
                        $value = $decoded !== null ? $decoded : $value;
                    }
                    return [
                        'field_id' => $answer->job_template_field_id,
                        'label' => $field?->label,
                        'value' => $value,
                    ];
                })->values()->all();
            }),

            'candidate' => $this->whenLoaded('candidate', function () {
                return [
                    'id' => $this->candidate->id,
                    'name' => $this->candidate->name,
                    'image' => $this->candidate->photo,
                ];
            }),

            'tags' => JobApplicationTagResource::collection(
                $this->whenLoaded('tags')
            ),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function formatJobLocation(Job $job): ?array
    {
        $parts = array_filter([$job->region, $job->country_code]);
        if ($parts === []) {
            return null;
        }
        return [
            'region' => $job->region,
            'country_code' => $job->country_code,
            'display' => implode(', ', $parts),
        ];
    }
}
