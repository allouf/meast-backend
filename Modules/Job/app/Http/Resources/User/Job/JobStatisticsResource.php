<?php

namespace Modules\Job\Http\Resources\User\Job;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $total = $this['total'] ?? 0;

        return [
            'total' => $total,

            'by_status' => collect($this['by_status'] ?? [])->map(fn($item, $status) => [
                'status' => $status,
                'count' => $item['count'] ?? (int)$item,
                'percentage' => $total > 0 ? round((($item['count'] ?? (int)$item) / $total) * 100, 2) : 0,
            ])->values(),

            'by_employment_type' => collect($this['by_employment_type'] ?? [])->map(fn($count, $type) => [
                'employment_type' => $type,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ])->values(),

            'by_work_mode' => collect($this['by_work_mode'] ?? [])->map(fn($count, $mode) => [
                'work_mode' => $mode,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ])->values(),

            'by_experience_level' => collect($this['by_experience_level'] ?? [])->map(fn($count, $level) => [
                'experience_level' => $level,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 2) : 0,
            ])->values(),

            'monthly_jobs' => collect($this['monthly_jobs'] ?? [])->map(fn($count, $month) => [
                'month' => $month,
                'count' => $count,
            ])->values(),

            'top_jobs' => $this['top_jobs'] ?? [],
        ];
    }
}