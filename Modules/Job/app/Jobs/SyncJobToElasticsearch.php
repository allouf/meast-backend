<?php

namespace Modules\Job\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Job\Library\JobElasticsearch;
use Modules\Job\Models\Job;

class SyncJobToElasticsearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Job $jobPosting;

    public function __construct(Job $jobPosting)
    {
        $this->jobPosting = $jobPosting;
    }

    public function handle(): void
    {
        $es = new JobElasticsearch();
        $es->indexJob($this->jobPosting);
        Log::info('Job synced to Elasticsearch', ['job_id' => $this->jobPosting->id, 'title' => $this->jobPosting->title]);
    }
}
