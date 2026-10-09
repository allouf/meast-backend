<?php

namespace App\Repositories\Interfaces;

use App\Models\BetaRegistration;

interface BetaRegistrationRepositoryInterface
{
    public function create(array $data): BetaRegistration;
}
