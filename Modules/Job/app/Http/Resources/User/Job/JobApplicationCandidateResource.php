<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

/**
 * Application/candidate as seen by employer (list and detail).
 */
class JobApplicationCandidateResource extends JsonResource
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
            'resume_url' => $this->resume_path ? FileManager::getUrl($this->resume_path) : null,
            // Cover letter is stored as plain text
            'cover_letter' => $this->cover_letter,
            'rejection_reason' => $this->when($this->status->value === 'REJECTED', $this->rejection_reason),
            'rating' => $this->rating,
            'rating_note' => $this->rating_note,
            'field_answers' => $this->whenLoaded('fieldAnswers', fn() => $this->fieldAnswers->map(function ($answer) {
                $field = $answer->relationLoaded('jobTemplateField') ? $answer->jobTemplateField : null;
                $value = $answer->value;
                if ($value !== null && (str_starts_with($value, '[') || str_starts_with($value, '{'))) {
                    $decoded = json_decode($value, true);
                    $value = $decoded !== null ? $decoded : $value;
                }
                return [
                    'field_id' => $answer->job_template_field_id,
                    'label' => $field?->label,
                    'value' => $value,
                ];
            })->values()->all()),
            'job' => $this->whenLoaded('job', fn() => [
                'id' => $this->job->id,
                'title' => $this->job->title,
            ]),
            'candidate' => $this->whenLoaded('candidate', fn() => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
                'phone' => $this->candidate->phone,
                'country_code' => $this->candidate->country_code,
                'region' => $this->candidate->region,
            ]),

            'tags' => $this->whenLoaded(
                'tags',
                fn() =>
                $this->tags->map(fn($tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'color' => $tag->color,
                ])->values()
            ),

            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
