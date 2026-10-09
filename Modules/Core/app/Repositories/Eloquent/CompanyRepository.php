<?php

namespace Modules\Core\Repositories\Eloquent;

use Modules\Core\Filters\CompanyFilter;
use Modules\Core\Models\Company;
use App\Traits\PaginateTrait;
use Modules\Core\Repositories\Interfaces\CompanyRepositoryInterface;

class CompanyRepository implements CompanyRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(Company $model)
    {
        $this->model = $model;
    }

    public function all(array $filters = [])
    {
        $query = $this->model
            ->whereHas('user', function ($query) {
                $query->whereNull('deletion_reason');
            })
            ->with(['user'])
            ->withCount('teamMembers')
            ->orderBy('id', 'desc');

        $query = (new CompanyFilter($filters))->apply($query);

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : null;
        return $this->paginate($query, $perPage);
    }

    public function findById(int $id)
    {
        return $this->model->with('user')->findOrFail($id);
    }

    public function findByUserId(int $userId): ?Company
    {
        return $this->model->with('user')->where('user_id', $userId)->first();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(Company $company, array $data)
    {
        return $company->update($data);
    }
}
