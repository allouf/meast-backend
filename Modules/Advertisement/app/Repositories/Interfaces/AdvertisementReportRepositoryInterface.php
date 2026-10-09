<?php

namespace Modules\Advertisement\Repositories\Interfaces;

interface AdvertisementReportRepositoryInterface
{
    public function create($data);

    public function find($id);

    public function getByAdvertisement($advertisementId, $filters = []);

    public function getByUser($userId, $filters = []);

    public function getAll($filters = []);

    public function update($id, $data);

    public function hasUserReported($userId, $advertisementId);
}
