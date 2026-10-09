<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Models\ProviderProject;
use Modules\Services\Repositories\Interfaces\ProviderProjectRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferRepositoryInterface;

class ProviderProjectService
{
    private const IMAGES_DIR = 'provider-projects/images';

    public function __construct(
        protected ProviderProjectRepositoryInterface $providerProjectRepository,
        protected ServiceOfferRepositoryInterface $serviceOfferRepository
    ) {}

    /** @return Collection<int, ProviderProject> */
    public function getByProvider(ServiceProvider $serviceProvider, array $filters = []): LengthAwarePaginator
    {
        return $this->providerProjectRepository->getByServiceProviderId($serviceProvider->id, $filters);
    }

    public function getById(int $id, ?int $serviceProviderId = null): ?ProviderProject
    {
        return $this->providerProjectRepository->findById($id, $serviceProviderId);
    }

    /**
     * @param  array<UploadedFile>|null  $imageFiles
     * @param  array<int>  $serviceOfferIds
     */
    public function create(ServiceProvider $serviceProvider, array $data, ?array $imageFiles = null, array $serviceOfferIds = []): ProviderProject
    {
        $data['service_provider_id'] = $serviceProvider->id;
        $data['images'] = $this->uploadImages($imageFiles);
        $serviceOfferIds = $this->filterOwnServiceOfferIds($serviceProvider->id, $serviceOfferIds);

        $project = $this->providerProjectRepository->create($data);
        $this->providerProjectRepository->syncServiceOffers($project, $serviceOfferIds);

        return $this->providerProjectRepository->findById($project->id, null, ['serviceOffers']);
    }

    /**
     * @param  array<UploadedFile>|null  $imageFiles  New images to append (or replace if existing logic). We'll replace all if provided.
     * @param  array<int>|null  $serviceOfferIds
     */
    public function update(ProviderProject $project, array $data, ?array $imageFiles = null, ?array $serviceOfferIds = null): ProviderProject
    {
        unset($data['images']);
        if ($imageFiles !== null && $imageFiles !== []) {
            $this->deleteImages($project->images);
            $data['images'] = $this->uploadImages($imageFiles);
        }

        $project = $this->providerProjectRepository->update($project, $data);
        if ($serviceOfferIds !== null) {
            $serviceOfferIds = $this->filterOwnServiceOfferIds($project->service_provider_id, $serviceOfferIds);
            $this->providerProjectRepository->syncServiceOffers($project, $serviceOfferIds);
        }

        return $this->providerProjectRepository->findById($project->id, null, ['serviceOffers']);
    }

    public function delete(ProviderProject $project): void
    {
        $this->deleteImages($project->images);
        $this->providerProjectRepository->delete($project);
    }

    /**
     * @param  array<UploadedFile>  $files
     * @return array<string>
     */
    private function uploadImages(?array $files): array
    {
        if ($files === null || $files === []) {
            return [];
        }
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = FileManager::upload(self::IMAGES_DIR, $file);
            }
        }
        return $paths;
    }

    /** @param  array<string>|null  $paths */
    private function deleteImages(?array $paths): void
    {
        if ($paths === null || $paths === []) {
            return;
        }
        foreach ($paths as $path) {
            FileManager::delete($path);
        }
    }

    /** @param  array<int>  $serviceOfferIds
     * @return array<int>
     */
    private function filterOwnServiceOfferIds(int $serviceProviderId, array $serviceOfferIds): array
    {
        $valid = $this->serviceOfferRepository->getIdsByServiceProviderAndIds($serviceProviderId, $serviceOfferIds);
        return array_values($valid);
    }
}
