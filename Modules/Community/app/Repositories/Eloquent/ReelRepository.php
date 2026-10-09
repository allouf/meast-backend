<?php

namespace Modules\Community\Repositories\Eloquent;

use Modules\Community\Filters\ReelFilter;
use App\Traits\PaginateTrait;
use Modules\Community\Models\Reel;
use Modules\Community\Repositories\Interfaces\ReelRepositoryInterface;

class ReelRepository implements ReelRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Reel $model)
    {
        $this->model = $model;
    }

    public function all($filters = [])
    {
        return $this->paginate($this->model->orderBy('id', 'desc')->with(['user', 'reactions', 'comments']));
    }

    public function find($id)
    {
        return $this->model->with(['user', 'reactions', 'comments.replies'])->find($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $reel = $this->model->find($id);
        if ($reel) {
            $reel->update($data);
            return $reel;
        }
        return null;
    }

    public function delete($id)
    {
        $reel = $this->model->find($id);
        return $reel ? $reel->delete() : false;
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['user', 'reactions', 'comments'])->where('user_id', $userId);
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
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
            ->where('caption', 'like', '%' . $query . '%');
        if (isset($filters['user_id'])) {
            $searchQuery->where('user_id', $filters['user_id']);
        }
        return $this->paginate($searchQuery);
    }

    public function getWithRelations($id, $relations = [])
    {
        return $this->model->with($relations)->find($id);
    }

    public function getReactionsCount($reelId)
    {
        return $this->model->find($reelId)->reactions()->count();
    }

    public function getCommentsCount($reelId)
    {
        return $this->model->find($reelId)->allComments()->count();
    }

    public function filter($data)
    {
        $builder = (new ReelFilter($data))->apply(Reel::query());
        return $this->paginate($builder->with(['user', 'reactions', 'comments'])->latest());
    }
}
