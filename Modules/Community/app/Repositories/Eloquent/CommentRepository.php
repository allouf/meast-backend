<?php

namespace Modules\Community\Repositories\Eloquent;

use Modules\Community\Models\Comment;
use Modules\Community\Models\Post;
use Modules\Community\Models\Reel;
use Modules\Community\Models\Story;
use Modules\Community\Repositories\Interfaces\CommentRepositoryInterface;

class CommentRepository implements CommentRepositoryInterface
{
    protected $model;

    public function __construct(Comment $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->with(['user', 'commentable', 'replies']);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['post_id'])) {
            $query->where('commentable_type', Post::class)->where('commentable_id', $filters['post_id']);
        }
        if (isset($filters['reel_id'])) {
            $query->where('commentable_type', Reel::class)->where('commentable_id', $filters['reel_id']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function find($id)
    {
        return $this->model->with(['user', 'commentable', 'replies.user'])->find($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $comment = $this->model->find($id);
        if ($comment) {
            $comment->update($data);
            return $comment;
        }
        return null;
    }

    public function delete($id)
    {
        $comment = $this->model->find($id);
        return $comment ? $comment->delete() : false;
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['user', 'commentable', 'replies'])->where('user_id', $userId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getByPost($postId, $filters = [])
    {
        $query = $this->model->with(['user', 'replies.user'])
            ->where('commentable_type', Post::class)
            ->where('commentable_id', $postId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getTopLevelForPost($postId)
    {
        return $this->model->with(['user', 'replies.user'])
            ->where('commentable_type', Post::class)
            ->where('commentable_id', $postId)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    }

    public function getReplies($commentId)
    {
        return $this->model->with(['user'])
            ->where('parent_id', $commentId)
            ->where('status', 'active')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getActive($filters = [])
    {
        $query = $this->model->with(['user', 'commentable', 'replies'])->where('status', 'active');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['post_id'])) {
            $query->where('commentable_type', Post::class)->where('commentable_id', $filters['post_id']);
        }
        if (isset($filters['reel_id'])) {
            $query->where('commentable_type', Reel::class)->where('commentable_id', $filters['reel_id']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function search($query, $filters = [])
    {
        $searchQuery = $this->model->with(['user', 'commentable', 'replies'])
            ->where('status', 'active')
            ->where('content', 'like', '%' . $query . '%');
        if (isset($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }
        if (isset($filters['post_id'])) {
            $searchQuery->where('commentable_type', Post::class)->where('commentable_id', $filters['post_id']);
        }
        if (isset($filters['reel_id'])) {
            $searchQuery->where('commentable_type', Reel::class)->where('commentable_id', $filters['reel_id']);
        }
        return $searchQuery->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getByReel($reelId, $filters = [])
    {
        $query = $this->model->with(['user', 'replies.user'])
            ->where('commentable_type', Reel::class)
            ->where('commentable_id', $reelId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getTopLevelForReel($reelId)
    {
        return $this->model->with(['user', 'replies.user'])
            ->where('commentable_type', Reel::class)
            ->where('commentable_id', $reelId)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    }

    public function getByStory($storyId, $filters = [])
    {
        $query = $this->model->with(['user', 'replies.user'])
            ->where('commentable_type', Story::class)
            ->where('commentable_id', $storyId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getTopLevelForStory($storyId)
    {
        return $this->model->with(['user', 'replies.user'])
            ->where('commentable_type', Story::class)
            ->where('commentable_id', $storyId)
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    }

    public function getWithRelations($id, $relations = [])
    {
        return $this->model->with($relations)->find($id);
    }
}
