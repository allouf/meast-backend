<?php

namespace App\Repositories\Interfaces;

use App\Models\Freelancer;
use App\Models\User;

interface FreelancerRepositoryInterface
{
    public function all();
    public function create(array $data);
    public function getDetails(int $id);
    public function getRequests(User $user);
    public function createRequestUpgrade(User $user);
    public function getRequestUserByToken(string $token);
    public function update(Freelancer $freelancer, array $data);
}
