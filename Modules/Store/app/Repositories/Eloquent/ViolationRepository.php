<?php

namespace Modules\Store\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Store\Models\Violation;
use Modules\Store\Models\ViolationFile;
use Modules\Store\Repositories\Interfaces\ViolationRepositoryInterface;

class ViolationRepository implements ViolationRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Violation $model)
    {
        $this->model = $model;
    }

    public function all(array $filters)
    {
        $query = $this->model
            ->with(['user', 'admin', 'reason', 'files', 'violatable'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['reason_id'])) {
            $query->where('reason_id', $filters['reason_id']);
        }

        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
            });
        }


        if (!empty($filters['type'])) {
            $typeMap = [
                'store'   => \Modules\Store\Models\Store::class,
                'product' => \Modules\Store\Models\Product::class,
                'post'    => \Modules\Community\Models\Post::class,
                'comment' => \Modules\Community\Models\Comment::class,
            ];

            $query->where('violatable_type', $typeMap[$filters['type']]);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }


        return $this->paginate($query);
    }

    public function findById($id)
    {
        return $this->model
        ->with(['user', 'admin', 'reason', 'files', 'violatable'])
        ->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }


    public function update(array $data, Violation $violation)
    {
        $violation->update($data);
        return $violation;
    }

    public function delete(Violation $violation)
    {
        return $violation->delete();
    }
}
