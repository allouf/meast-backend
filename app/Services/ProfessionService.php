<?php

namespace App\Services;

use App\Repositories\Interfaces\ProfessionRepositoryInterface;

class ProfessionService
{
    protected $professionRepository;

    public function __construct(ProfessionRepositoryInterface $professionRepository)
    {
        $this->professionRepository = $professionRepository;
    }

    public function all()
    {
        return $this->professionRepository->all();
    }

    public function findById($id)
    {
        return $this->professionRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->professionRepository->create($data);
    }

    public function update(array $data,$id)
    {
        return $this->professionRepository->update($data,$id);
    }

    public function delete($id)
    {
        return $this->professionRepository->delete($id);
    }
}
