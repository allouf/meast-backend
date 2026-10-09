<?php

namespace Modules\Advertisement\Repositories\Eloquent;

use App\Traits\PaginateTrait;
use Modules\Advertisement\Filters\AdvertisementReportFilter;
use Modules\Advertisement\Models\AdvertisementReport;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementReportRepositoryInterface;

class AdvertisementReportRepository implements AdvertisementReportRepositoryInterface
{
    use PaginateTrait;

    protected $model;

    public function __construct(AdvertisementReport $model)
    {
        $this->model = $model;
    }

    public function create($data)
    {
        return $this->model->create($data);
    }

    public function find($id)
    {
        return $this->model->with(['advertisement.user', 'reporter', 'reviewer'])->find($id);
    }

    public function getByAdvertisement($advertisementId, $filters = [])
    {
        $filters = array_merge($filters, ['advertisement_id' => $advertisementId]);
        $query = $this->model->with(['reporter', 'reviewer'])->orderBy('created_at', 'desc');
        $query = (new AdvertisementReportFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getByUser($userId, $filters = [])
    {
        $query = $this->model->with(['advertisement', 'reviewer'])
            ->where('reported_by', $userId)
            ->orderBy('created_at', 'desc');
        $query = (new AdvertisementReportFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function getAll($filters = [])
    {
        $query = $this->model->with(['advertisement.user', 'reporter', 'reviewer'])->orderBy('created_at', 'desc');
        $query = (new AdvertisementReportFilter($filters))->apply($query);
        return $this->paginate($query, $filters['per_page'] ?? null);
    }

    public function update($id, $data)
    {
        $report = $this->model->find($id);
        if ($report) {
            $report->update($data);
            return $report->fresh();
        }
        return null;
    }

    public function hasUserReported($userId, $advertisementId)
    {
        return $this->model->where('reported_by', $userId)->where('advertisement_id', $advertisementId)->exists();
    }
}
