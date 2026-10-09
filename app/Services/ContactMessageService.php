<?php

namespace App\Services;

use App\Repositories\Interfaces\ContactMessageRepositoryInterface;

class ContactMessageService
{
    protected $contactMessageRepository;

    public function __construct(ContactMessageRepositoryInterface $contactMessageRepository)
    {
        $this->contactMessageRepository = $contactMessageRepository;
    }

    public function all()
    {
        return $this->contactMessageRepository->all();
    }

    public function findById($id)
    {
        return $this->contactMessageRepository->findById($id);
    }

    public function create(array $data)
    {
        return $this->contactMessageRepository->create($data);
    }

    public function update(array $data,$id)
    {
        return $this->contactMessageRepository->update($data,$id);
    }

    public function delete($id)
    {
        return $this->contactMessageRepository->delete($id);
    }

    public function filter($data)
    {
        return $this->contactMessageRepository->filter($data);
    }
}
