<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Utilities\FileManager;

/**
 * Full candidate details for employer: application, candidate info, answers, attachments, rating, notes, tags.
 */
class JobApplicationCandidateDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fieldAnswers = $this->whenLoaded('fieldAnswers', function () {
            return $this->fieldAnswers->map(function ($answer) {
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
            })->values()->all();
        });

        $attachments = [];
        if ($this->resume_path) {
            $attachments[] = [
                'type' => 'resume',
                'label' => 'Resume / CV',
                'url' => FileManager::getUrl($this->resume_path),
                'uploaded_at' => $this->created_at?->toDateTimeString(),
            ];
        }
        if ($this->relationLoaded('internalAttachments') && $this->internalAttachments) {
            foreach ($this->internalAttachments as $att) {
                $attachments[] = [
                    'id' => $att->id,
                    'type' => 'internal',
                    'label' => $att->original_name,
                    'url' => url('api/v1/user/job-applications/attachments/' . $att->id . '/download'),
                    'mime_type' => $att->mime_type,
                    'size' => $att->size,
                    'uploaded_at' => $att->created_at?->toDateTimeString(),
                ];
            }
        }

        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'status' => $this->status->value,
            'rating' => $this->rating,
            'cover_letter' => $this->cover_letter,
            'rejection_reason' => $this->when($this->status->value === 'REJECTED', $this->rejection_reason),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
            'job' => $this->whenLoaded('job', fn () => [
                'id' => $this->job->id,
                'title' => $this->job->title,
            ]),
            'candidate' => $this->whenLoaded('candidate', fn () => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
                'phone' => $this->candidate->phone,
                'country_code' => $this->candidate->country_code,
                'region' => $this->candidate->region,
                'photo' => $this->candidate->photo ? FileManager::getUrl($this->candidate->photo) : null,
            ]),
            'field_answers' => $fieldAnswers,
            'attachments' => $attachments,
            'notes' => JobApplicationNoteResource::collection($this->whenLoaded('notes')),
            'tags' => JobApplicationTagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
