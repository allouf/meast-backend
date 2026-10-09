<?php

namespace Modules\Job\Enums;

/** Kanban stages for job applications. */
enum ApplicationStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Shortlisted = 'shortlisted';
    case Interviews = 'interviews';
    case JobOffer = 'job_offer';
    case Hired = 'hired';
    case Rejected = 'rejected';

    /** @return array<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
