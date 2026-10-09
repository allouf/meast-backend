<?php

namespace Modules\Job\Observers;

use Modules\Job\Jobs\SyncJobToElasticsearch;
use Modules\Job\Library\JobElasticsearch;
use Modules\Job\Models\Job;

class JobObserver
{
    protected JobElasticsearch $es;

    public function __construct()
    {
        $this->es = new JobElasticsearch();
    }

    public function created(Job $job): void
    {
        SyncJobToElasticsearch::dispatch($job);
    }

    public function updated(Job $job): void
    {
        SyncJobToElasticsearch::dispatch($job);
    }

    public function deleted(Job $job): void
    {
        $this->es->deleteDocument('job_postings', $job->id, true);
    }

    public function restored(Job $job): void
    {
        SyncJobToElasticsearch::dispatch($job);
    }
}
