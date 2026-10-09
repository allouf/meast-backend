<?php

namespace Modules\Store\Repositories\Interfaces;

use Modules\Store\Models\Violation;
interface ViolationRepositoryInterface
{
    public function all(array $filters);

    public function findById($id);

    public function create(array $data);

    public function update(array $data, Violation $violation);

    public function delete(Violation $violation);
}
