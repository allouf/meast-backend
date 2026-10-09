<?php

namespace Modules\Community\Repositories\Eloquent;

use Modules\Community\Filters\PostFilter;
use App\Traits\PaginateTrait;
use Modules\Community\Models\Post;
use Modules\Community\Models\PostLog;
use Modules\Community\Repositories\Interfaces\PostRepositoryInterface;

class PostRepository implements PostRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Post $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        $query = $this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'comments'])->withCount('reports');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['search'])) {
            $query->where('content', 'like', '%' . $filters['search'] . '%');
        }
        return $this->paginate($query);
    }

    public function find($id)
    {
        return $this->model->with(['user', 'reactions', 'comments.replies', 'logs.user'])->withCount('reports')->find($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $post = $this->model->find($id);
        if ($post) {
            $post->update($data);
            return $post;
        }
        return null;
    }

    public function logPostChange($postId, $userId, $changes)
    {
        PostLog::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'changes' => $changes,
        ]);
    }

    public function delete($id)
    {
        $post = $this->model->find($id);
        return $post ? $post->delete() : false;
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['user', 'reactions', 'comments'])->where('user_id', $userId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $this->paginate($query->orderBy('created_at', 'desc'));
    }

    public function getActive($filters = [])
    {
        $query = $this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'comments'])->where('status', 'active');
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $this->paginate($query);
    }

    public function getFeatured()
    {
        return $this->model->with(['user', 'reactions', 'comments'])
            ->where('status', 'active')
            ->where('is_featured', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function search($query, $filters = [])
    {
        $searchQuery = $this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'comments'])
            ->where('status', 'active')
            ->where('content', 'like', '%' . $query . '%');
        if (isset($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }
        return $this->paginate($searchQuery);
    }

    public function getWithRelations($id, $relations = [])
    {
        return $this->model->with($relations)->find($id);
    }

    public function getReactionsCount($postId)
    {
        return $this->model->find($postId)->reactions()->count();
    }

    public function getCommentsCount($postId)
    {
        return $this->model->find($postId)->allComments()->count();
    }

    public function filter($data)
    {
        $builder = (new PostFilter($data))->apply(Post::query());
        return $this->paginate($builder->with(['user', 'reactions', 'comments'])->latest());
    }
}
