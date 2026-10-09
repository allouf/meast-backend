<?php

namespace Modules\Advertisement\Services;

use App\Utilities\FileManager;
use Illuminate\Support\Facades\Auth;
use Modules\Advertisement\Repositories\Interfaces\AdvertisementReportRepositoryInterface;

class AdvertisementReportService
{
    public function __construct(
        protected AdvertisementReportRepositoryInterface $reportRepository
    ) {}

    public function createReport($data)
    {
        $data['reported_by'] = Auth::id();
        $data['status'] = 'pending';

        if (isset($data['images']) && is_array($data['images'])) {
            $uploadedImages = [];
            foreach ($data['images'] as $image) {
                if ($image) {
                    $uploadedImages[] = FileManager::upload('advertisement-reports', $image);
                }
            }
            $data['images'] = $uploadedImages;
        }

        return $this->reportRepository->create($data);
    }

    public function getReportById($id)
    {
        return $this->reportRepository->find($id);
    }

    public function getReportsByAdvertisement($advertisementId, $filters = [])
    {
        return $this->reportRepository->getByAdvertisement($advertisementId, $filters);
    }

    public function getMyReports($filters = [])
    {
        return $this->reportRepository->getByUser(Auth::id(), $filters);
    }

    public function getAllReports($filters = [])
    {
        return $this->reportRepository->getAll($filters);
    }

    public function updateReport($id, $data)
    {
        return $this->reportRepository->update($id, $data);
    }

    public function hasUserReported($advertisementId)
    {
        return $this->reportRepository->hasUserReported(Auth::id(), $advertisementId);
    }
}
