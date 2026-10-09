<?php

namespace App\Repositories\Eloquent;

use App\Models\BetaRegistration;
use App\Repositories\Interfaces\BetaRegistrationRepositoryInterface;

class BetaRegistrationRepository implements BetaRegistrationRepositoryInterface
{
    protected $model;

    public function __construct(BetaRegistration $model)
    {
        $this->model = $model;
    }

    public function create(array $data): BetaRegistration
    {
        return $this->model->create($data);
    }
}
