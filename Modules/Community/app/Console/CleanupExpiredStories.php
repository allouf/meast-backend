<?php

namespace Modules\Community\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Community\Services\StoryService;

class CleanupExpiredStories extends Command
{
    protected $signature = 'stories:cleanup-expired';

    protected $description = 'Clean up expired stories from the database';

    public function __construct(protected StoryService $storyService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $startTime = now();
        $this->info('Starting cleanup of expired stories...');

        Log::info('[STORY CLEANUP] Starting cleanup of expired stories at ' . $startTime->format('Y-m-d H:i:s'));

        $deletedCount = $this->storyService->deleteExpiredStories();

        $endTime = now();
        $duration = $startTime->diffInSeconds($endTime);

        $message = "Successfully deleted {$deletedCount} expired stories in {$duration} seconds.";
        $this->info($message);

        Log::info("[STORY CLEANUP] {$message} Completed at " . $endTime->format('Y-m-d H:i:s'));

        return 0;
    }
}
