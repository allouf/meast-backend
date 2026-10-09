<?php

namespace Modules\Community\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Community\Repositories\Eloquent\CommentRepository;
use Modules\Community\Repositories\Eloquent\PostRepository;
use Modules\Community\Repositories\Eloquent\PostReportRepository;
use Modules\Community\Repositories\Eloquent\ReactionRepository;
use Modules\Community\Repositories\Eloquent\ReelRepository;
use Modules\Community\Repositories\Eloquent\StoryRepository;
use Modules\Community\Repositories\Interfaces\CommentRepositoryInterface;
use Modules\Community\Repositories\Interfaces\PostRepositoryInterface;
use Modules\Community\Repositories\Interfaces\PostReportRepositoryInterface;
use Modules\Community\Repositories\Interfaces\ReactionRepositoryInterface;
use Modules\Community\Repositories\Interfaces\ReelRepositoryInterface;
use Modules\Community\Repositories\Interfaces\StoryRepositoryInterface;

class CommunityServiceProvider extends ServiceProvider
{
    protected string $name = 'Community';
    protected string $nameLower = 'community';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Community\Console\CleanupExpiredStories::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->registerRepositories();
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerRepositories(): void
    {
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(ReactionRepositoryInterface::class, ReactionRepository::class);
        $this->app->bind(PostReportRepositoryInterface::class, PostReportRepository::class);
        $this->app->bind(ReelRepositoryInterface::class, ReelRepository::class);
        $this->app->bind(StoryRepositoryInterface::class, StoryRepository::class);
    }
}
