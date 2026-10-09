<?php

namespace App\Services;

use App\Repositories\Interfaces\BannerRepositoryInterface;

class BannerService
{
    protected $bannerRepository;

    public function __construct(BannerRepositoryInterface $bannerRepository)
    {
        $this->bannerRepository = $bannerRepository;
    }

    public function all()
    {
        return $this->bannerRepository->all();
    }

    public function findById($id)
    {
        return $this->bannerRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->bannerRepository->create($data);
    }

    public function update(array $data,$id)
    {
        return $this->bannerRepository->update($data,$id);
    }

    public function delete($id)
    {
        return $this->bannerRepository->delete($id);
    }
}
