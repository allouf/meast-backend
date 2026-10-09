<?php

namespace Modules\Community\Services;

use Modules\Community\Repositories\Interfaces\PostReportRepositoryInterface;

class PostReportService
{
    protected $postReportRepository;

    public function __construct(PostReportRepositoryInterface $postReportRepository)
    {
        $this->postReportRepository = $postReportRepository;
    }

    public function all()
    {
        return $this->postReportRepository->all();
    }

    public function create($data)
    {
        return $this->postReportRepository->create($data);
    }

    public function show($id)
    {
        return $this->postReportRepository->findById($id);
    }

    public function destroy($id)
    {
        return $this->postReportRepository->delete($id);
    }

    public function getByPost($postId)
    {
        return $this->postReportRepository->getByPost($postId);
    }
    
}
