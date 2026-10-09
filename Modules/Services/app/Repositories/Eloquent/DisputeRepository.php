<?php

namespace Modules\Services\Repositories\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Services\Models\ServiceOfferOrderDispute;
use Modules\Services\Repositories\Interfaces\DisputeRepositoryInterface;

class DisputeRepository implements DisputeRepositoryInterface
{
    protected $model;

    public function __construct(ServiceOfferOrderDispute $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->all();
    }

    public function findById($id)
    {
        return $this->model->findOrFail($id);
    }

    public function findByUser(int $userId, int $perPage = 15):LengthAwarePaginator
    {
        return $this->model
            ->with(['order', 'reason', 'evidences'])
            ->where(function ($q) use ($userId) {

                $q->where('buyer_id', $userId)

                    ->orWhereHas('provider', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    })

                    ->orWhereHas('order', function ($q) use ($userId) {

                        $q->where('buyer_id', $userId)

                            ->orWhereHas('provider', function ($q) use ($userId) {
                                $q->where('user_id', $userId);
                            });
                    });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findWithDetails(int $id)
    {
        return $this->model
            ->with([
                'order',
                'order.offer',
                'order.offer.serviceCategory',
                'order.revisions',
                'order.extensions',
                'order.deliveries',
                'order.latestEscrow',
                'buyer',
                'provider',
                'reason',
                'evidences'
            ])
            ->findOrFail($id);
    }


    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(array $data, ServiceOfferOrderDispute $dispute)
    {
        $dispute->update($data);
        return $dispute;
    }

    public function delete(ServiceOfferOrderDispute $dispute)
    {
        return $dispute->delete();
    }

    public function addEvidence(ServiceOfferOrderDispute $dispute, array $data)
    {
        return $dispute->evidences()->create($data);
    }
}
