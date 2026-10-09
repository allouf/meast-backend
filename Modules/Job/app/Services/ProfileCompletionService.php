<?php

namespace Modules\Job\Services;

use Modules\Core\Models\User;
use Illuminate\Support\Facades\Cache;

class ProfileCompletionService
{
    /**
     * Return cache key for a user's profile completion.
     */
    public function cacheKey(int $userId): string
    {
        $prefix = config('profile_completion.cache.key_prefix', 'user_job_profile_completion');

        return "{$prefix}:{$userId}";
    }

    /**
     * Get profile completion percentage (0–100) for the user, using cache.
     * Weighted by config profile_completion.sections.
     */
    public function getPercentage(User $user): int
    {
        $ttl = config('profile_completion.cache.ttl_seconds', 3600);
        $key = $this->cacheKey($user->id);

        return (int) Cache::remember($key, $ttl, function () use ($user) {
            return $this->computePercentage($user);
        });
    }

    /**
     * Compute completion without cache (used inside remember closure and after invalidation).
     */
    public function computePercentage(User $user): int
    {
        $sections = config('profile_completion.sections', []);
        if (empty($sections)) {
            return 0;
        }

        $user->loadCount(array_keys($sections));
        $score = 0;

        foreach ($sections as $relation => $weight) {
            $count = (int) ($user->{"{$relation}_count"} ?? 0);
            if ($count > 0) {
                $score += $weight;
            }
        }

        return min(100, $score);
    }

    /**
     * Invalidate cached completion for the user (call when profile data changes).
     */
    public function invalidate(User $user): void
    {
        Cache::forget($this->cacheKey($user->id));
    }

    /**
     * Invalidate by user id when user model is not available.
     */
    public function invalidateForUserId(int $userId): void
    {
        Cache::forget($this->cacheKey($userId));
    }

    /**
     * Minimum percentage required to use job-postings (apply/list/details).
     */
    public function minimumPercentageToApply(): int
    {
        return (int) config('profile_completion.minimum_percentage_to_apply', 0);
    }
}
