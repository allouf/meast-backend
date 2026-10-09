<?php

namespace Modules\Community\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Community\Models\PostReport;
use Modules\Community\Repositories\Interfaces\PostReportRepositoryInterface;

class PostReportRepository implements PostReportRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(PostReport $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->paginate($this->model->with(['post', 'reporter'])->orderBy('created_at', 'desc'));
    }

    public function findById($id)
    {
        return $this->model->with(['post', 'reporter'])->findOrFail($id);
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function delete($id)
    {
        $report = $this->model->find($id);
        return $report?->delete();
    }

    public function getByPost($postId)
    {
        return $this->model->where('post_id', $postId)->with('reporter')->get();
    }
}
