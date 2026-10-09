<?php

namespace Modules\Community\Repositories\Eloquent;

use Modules\Community\Filters\StoryFilter;
use App\Traits\PaginateTrait;
use Modules\Community\Models\Story;
use Modules\Community\Models\StoryView;
use Modules\Community\Repositories\Interfaces\StoryRepositoryInterface;

class StoryRepository implements StoryRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Story $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'replies'])->notExpired();
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $this->paginate($query);
    }

    public function find($id)
    {
        return $this->model->with(['user', 'reactions', 'replies.replies'])->find($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $story = $this->model->find($id);
        if ($story) {
            $story->update($data);
            return $story;
        }
        return null;
    }

    public function delete($id)
    {
        $story = $this->model->find($id);
        return $story ? $story->delete() : false;
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['user', 'reactions', 'replies'])->where('user_id', $userId)->notExpired();
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $this->paginate($query->orderBy('created_at', 'desc'));
    }

    public function getActive($filters = [])
    {
        $query = $this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'replies'])
            ->where('status', 'active')->notExpired();
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $this->paginate($query);
    }

    public function getFeatured()
    {
        return $this->model->with(['user', 'reactions', 'replies'])
            ->where('status', 'active')
            ->where('is_featured', true)
            ->notExpired()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function search($query, $filters = [])
    {
        $searchQuery = $this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'replies'])
            ->where('status', 'active')->notExpired()
            ->where('content', 'like', '%' . $query . '%');
        if (isset($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }
        if (isset($filters['type'])) {
            $searchQuery->where('type', $filters['type']);
        }
        return $this->paginate($searchQuery);
    }

    public function getWithRelations($id, $relations = [])
    {
        return $this->model->with($relations)->find($id);
    }

    public function getReactionsCount($storyId)
    {
        return $this->model->find($storyId)->reactions()->count();
    }

    public function getRepliesCount($storyId)
    {
        return $this->model->find($storyId)->allReplies()->count();
    }

    public function filter($data)
    {
        $builder = (new StoryFilter($data))->apply(Story::query());
        return $this->paginate($builder->with(['user', 'reactions', 'replies'])->latest());
    }

    public function getNotExpired($filters = [])
    {
        $query = $this->model->with(['user', 'reactions', 'replies'])->notExpired()->where('status', 'active');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $this->paginate($query->orderBy('created_at', 'desc'));
    }

    public function getExpiredStories()
    {
        return $this->model->where('expires_at', '<', now())->get();
    }

    public function deleteExpiredStories()
    {
        return $this->model->where('expires_at', '<', now())->delete();
    }

    public function incrementViews($storyId)
    {
        $story = $this->model->find($storyId);
        return $story ? $story->increment('views_count') : false;
    }

    public function getMostViewed($limit = 10)
    {
        return $this->model->with(['user', 'reactions', 'replies'])
            ->where('status', 'active')
            ->notExpired()
            ->withCount('views')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get();
    }

    public function addStoryView($storyId, $userId)
    {
        return StoryView::create([
            'story_id' => $storyId,
            'user_id' => $userId,
            'viewed_at' => now()
        ]);
    }

    public function getStoryViews($storyId)
    {
        return StoryView::where('story_id', $storyId)
            ->with('user:id,name,photo,type')
            ->orderBy('viewed_at', 'desc')
            ->get();
    }
}
