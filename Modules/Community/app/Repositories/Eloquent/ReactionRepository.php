<?php

namespace Modules\Community\Repositories\Eloquent;

use Modules\Community\Models\Reaction;
use Modules\Community\Models\Post;
use Modules\Community\Models\Reel;
use Modules\Community\Models\Story;
use Modules\Community\Repositories\Interfaces\ReactionRepositoryInterface;

class ReactionRepository implements ReactionRepositoryInterface
{
    protected $model;

    public function __construct(Reaction $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->with(['user', 'reactionable']);
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['post_id'])) {
            $query->where('reactionable_type', Post::class)->where('reactionable_id', $filters['post_id']);
        }
        if (isset($filters['reel_id'])) {
            $query->where('reactionable_type', Reel::class)->where('reactionable_id', $filters['reel_id']);
        }
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function find($id)
    {
        return $this->model->with(['user', 'reactionable'])->find($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $reaction = $this->model->find($id);
        if ($reaction) {
            $reaction->update($data);
            return $reaction;
        }
        return null;
    }

    public function delete($id)
    {
        $reaction = $this->model->find($id);
        return $reaction ? $reaction->delete() : false;
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['user', 'reactionable'])->where('user_id', $userId);
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getByPost($postId, $filters = [])
    {
        $query = $this->model->with(['user'])
            ->where('reactionable_type', Post::class)
            ->where('reactionable_id', $postId);
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getByType($postId, $type)
    {
        return $this->model->with(['user'])
            ->where('reactionable_type', Post::class)
            ->where('reactionable_id', $postId)
            ->where('type', $type)
            ->get();
    }

    public function getReactionsCount($postId)
    {
        return $this->model->where('reactionable_type', Post::class)
            ->where('reactionable_id', $postId)
            ->count();
    }

    public function getByReel($reelId, $filters = [])
    {
        $query = $this->model->with(['user'])
            ->where('reactionable_type', Reel::class)
            ->where('reactionable_id', $reelId);
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function toggleForReel($userId, $reelId, $type)
    {
        $existingReaction = $this->model->where('user_id', $userId)
            ->where('reactionable_type', Reel::class)
            ->where('reactionable_id', $reelId)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                $existingReaction->delete();
                return null;
            }
            $existingReaction->update(['type' => $type]);
            return $existingReaction;
        }
        return $this->model->create([
            'user_id' => $userId,
            'reactionable_type' => Reel::class,
            'reactionable_id' => $reelId,
            'type' => $type
        ]);
    }

    public function getUserReactionForReel($userId, $reelId)
    {
        return $this->model->where('user_id', $userId)
            ->where('reactionable_type', Reel::class)
            ->where('reactionable_id', $reelId)
            ->first();
    }

    public function getReactionsCountForReel($reelId)
    {
        return $this->model->where('reactionable_type', Reel::class)
            ->where('reactionable_id', $reelId)
            ->count();
    }

    public function getReactionsByTypeForReel($reelId)
    {
        return $this->model->where('reactionable_type', Reel::class)
            ->where('reactionable_id', $reelId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }

    public function getByTypeForReel($reelId, $type)
    {
        return $this->model->with(['user'])
            ->where('reactionable_type', Reel::class)
            ->where('reactionable_id', $reelId)
            ->where('type', $type)
            ->get();
    }

    public function getByStory($storyId, $filters = [])
    {
        $query = $this->model->with(['user'])
            ->where('reactionable_type', Story::class)
            ->where('reactionable_id', $storyId);
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function toggleForStory($userId, $storyId, $type)
    {
        $existingReaction = $this->model->where('user_id', $userId)
            ->where('reactionable_type', Story::class)
            ->where('reactionable_id', $storyId)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                $existingReaction->delete();
                return null;
            }
            $existingReaction->update(['type' => $type]);
            return $existingReaction;
        }
        return $this->model->create([
            'user_id' => $userId,
            'reactionable_type' => Story::class,
            'reactionable_id' => $storyId,
            'type' => $type
        ]);
    }

    public function getUserReactionForStory($userId, $storyId)
    {
        return $this->model->where('user_id', $userId)
            ->where('reactionable_type', Story::class)
            ->where('reactionable_id', $storyId)
            ->first();
    }

    public function getReactionsCountForStory($storyId)
    {
        return $this->model->where('reactionable_type', Story::class)
            ->where('reactionable_id', $storyId)
            ->count();
    }

    public function getReactionsByTypeForStory($storyId)
    {
        return $this->model->where('reactionable_type', Story::class)
            ->where('reactionable_id', $storyId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }

    public function getByTypeForStory($storyId, $type)
    {
        return $this->model->with(['user'])
            ->where('reactionable_type', Story::class)
            ->where('reactionable_id', $storyId)
            ->where('type', $type)
            ->get();
    }

    public function toggle($userId, $reactionableId, $type, $reactionableType = Post::class)
    {
        $existingReaction = $this->model->where('user_id', $userId)
            ->where('reactionable_type', $reactionableType)
            ->where('reactionable_id', $reactionableId)
            ->first();

        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                $existingReaction->delete();
                return null;
            }
            $existingReaction->update(['type' => $type]);
            return $existingReaction;
        }
        return $this->model->create([
            'user_id' => $userId,
            'reactionable_type' => $reactionableType,
            'reactionable_id' => $reactionableId,
            'type' => $type
        ]);
    }

    public function getUserReaction($userId, $reactionableId, $reactionableType = Post::class)
    {
        return $this->model->where('user_id', $userId)
            ->where('reactionable_type', $reactionableType)
            ->where('reactionable_id', $reactionableId)
            ->first();
    }

    public function getReactionsByType($reactionableId, $reactionableType = Post::class)
    {
        return $this->model->where('reactionable_type', $reactionableType)
            ->where('reactionable_id', $reactionableId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }
}
