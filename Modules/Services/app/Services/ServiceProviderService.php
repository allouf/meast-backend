<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Modules\Core\Models\User;
use Modules\Services\Enums\ProviderVerificationStatus;
use Modules\Services\Models\ServiceProvider;
use Modules\Services\Repositories\Interfaces\ServiceProviderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ServiceProviderService
{
    private const IDENTITY_DOCUMENT_DIR = 'service-providers/identity';

    private const LOGO_DIR = 'service-providers/logo';

    private const COVER_DIR = 'service-providers/cover';

    public function __construct(
        protected ServiceProviderRepositoryInterface $serviceProviderRepository
    ) {}

    public function getAllForAdmin(array $filters = []): LengthAwarePaginator
    {
        return $this->serviceProviderRepository->getPaginated($filters);
    }

    public function getVerificationRequestsForAdmin(array $filters = []): LengthAwarePaginator
    {
        return $this->serviceProviderRepository->getVerificationRequestsPaginated($filters);
    }

    public function getVerificationRequestByIdForAdmin(int $id): ?ServiceProvider
    {
        return $this->serviceProviderRepository->findById($id, [
            'user:id,name,email,photo',
            'languages',
        ]);
    }

    public function approveVerification(int $id, ?string $notes = null): ServiceProvider
    {
        $profile = $this->findVerificationRequestOrFail($id);

        return $this->serviceProviderRepository->update($profile, [
            'verification_status' => ProviderVerificationStatus::Approved,
            'verification_notes' => $notes ?? $profile->verification_notes,
        ], ['user', 'languages']);
    }

    public function rejectVerification(int $id, string $notes): ServiceProvider
    {
        $profile = $this->findVerificationRequestOrFail($id);

        return $this->serviceProviderRepository->update($profile, [
            'verification_status' => ProviderVerificationStatus::Rejected,
            'verification_notes' => $notes,
        ], ['user', 'languages']);
    }

    public function submitVerification(
        User $user,
        array $data = [],
        ?UploadedFile $identityDocument = null
    ): ServiceProvider {
        $profile = $this->serviceProviderRepository->getByUserId($user->id);

        if (! $profile) {
            throw new HttpException(404, __('services::messages.service_provider.not_found'));
        }

        $status = $profile->verification_status;

        if ($status === ProviderVerificationStatus::Approved) {
            throw new HttpException(422, __('services::messages.service_provider.verification_already_approved'));
        }

        if ($status === ProviderVerificationStatus::Pending) {
            throw new HttpException(422, __('services::messages.service_provider.verification_already_pending'));
        }

        $updateData = [
            'verification_status' => ProviderVerificationStatus::Pending,
        ];

        if ($identityDocument) {
            if ($profile->identity_document) {
                FileManager::delete($profile->identity_document);
            }
            $updateData['identity_document'] = FileManager::upload(
                self::IDENTITY_DOCUMENT_DIR,
                $identityDocument
            );
        }

        if (array_key_exists('verification_notes', $data)) {
            $updateData['verification_notes'] = $data['verification_notes'];
        }

        if (empty($updateData['identity_document']) && empty($profile->identity_document)) {
            throw new HttpException(422, __('services::messages.service_provider.verification_identity_required'));
        }

        return $this->serviceProviderRepository->update($profile, $updateData, ['user', 'languages']);
    }

    public function getByIdForAdmin(int $id): ?ServiceProvider
    {
        return $this->serviceProviderRepository->findById($id, [
            'user:id,name,email,photo',
            'languages',
            'serviceOffers.serviceCategory',
        ]);
    }

    public function getByUser(User $user): ?ServiceProvider
    {
        return $this->serviceProviderRepository->getByUserId($user->id, ['user', 'languages']);
    }

    public function getVerificationStatusForProvider(int $providerId, User $user): ServiceProvider
    {
        $profile = $this->serviceProviderRepository->findById($providerId);

        if (! $profile) {
            throw new HttpException(404, __('services::messages.service_provider.not_found'));
        }

        if ($profile->user_id !== $user->id) {
            throw new HttpException(403, __('services::messages.service_provider.forbidden'));
        }

        return $profile;
    }

    public function create(User $user, array $data, ?UploadedFile $identityDocument = null): ServiceProvider
    {
        $existing = $this->serviceProviderRepository->getByUserId($user->id);
        if ($existing) {
            throw new HttpException(422, __('services::messages.service_provider.already_exists'));
        }

        $data['user_id'] = $user->id;
        $data['type'] = $data['type'] ?? ServiceProvider::TYPE_INDIVIDUAL;
        if ($identityDocument) {
            $data['identity_document'] = FileManager::upload(self::IDENTITY_DOCUMENT_DIR, $identityDocument);
            $this->applyPendingVerificationStatus($data);
        }

        if (! empty($data['logo']) && $data['logo'] instanceof UploadedFile) {
            $data['logo'] = FileManager::upload(self::LOGO_DIR, $data['logo']);
        } else {
            unset($data['logo']);
        }

        if (! empty($data['cover']) && $data['cover'] instanceof UploadedFile) {
            $data['cover'] = FileManager::upload(self::COVER_DIR, $data['cover']);
        } else {
            unset($data['cover']);
        }

        $languageIds = $data['language_ids'] ?? [];
        unset($data['language_ids']);

        $profile = $this->serviceProviderRepository->create($data);
        if ($languageIds !== []) {
            $this->serviceProviderRepository->syncLanguages($profile, $languageIds);
        }

        $withLanguages = $this->serviceProviderRepository->getByUserId($user->id, ['languages']);
        return $withLanguages ?? $profile;
    }

    public function update(ServiceProvider $profile, array $data, ?UploadedFile $identityDocument = null): ServiceProvider
    {
        unset($data['identity_document']);
        if (isset($data['language_ids'])) {
            $this->serviceProviderRepository->syncLanguages($profile, $data['language_ids']);
            unset($data['language_ids']);
        }

        if ($identityDocument) {
            if ($profile->identity_document) {
                FileManager::delete($profile->identity_document);
            }
            $data['identity_document'] = FileManager::upload(self::IDENTITY_DOCUMENT_DIR, $identityDocument);
            $this->applyPendingVerificationStatus($data, $profile);
        }

        if (! empty($data['logo']) && $data['logo'] instanceof UploadedFile) {
            $data['logo'] = FileManager::update(self::LOGO_DIR, $data['logo'], $profile->logo);
        } else {
            unset($data['logo']);
        }

        if (! empty($data['cover']) && $data['cover'] instanceof UploadedFile) {
            $data['cover'] = FileManager::update(self::COVER_DIR, $data['cover'], $profile->cover);
        } else {
            unset($data['cover']);
        }

        return $this->serviceProviderRepository->update($profile, $data, ['languages']);
    }

    private function findVerificationRequestOrFail(int $id): ServiceProvider
    {
        $profile = $this->serviceProviderRepository->findById($id);

        if (! $profile) {
            throw new HttpException(404, __('services::messages.service_provider.not_found'));
        }

        if ($profile->verification_status !== ProviderVerificationStatus::Pending) {
            throw new HttpException(422, __('services::messages.service_provider.verification_not_pending'));
        }

        return $profile;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyPendingVerificationStatus(array &$data, ?ServiceProvider $profile = null): void
    {
        $current = $profile?->verification_status;

        if ($current === ProviderVerificationStatus::Approved) {
            return;
        }

        if ($current === ProviderVerificationStatus::Pending) {
            return;
        }

        $data['verification_status'] = ProviderVerificationStatus::Pending;
    }

    public function removeProfileField(User $user, string $field): ServiceProvider
    {
        $profile = $this->serviceProviderRepository->getByUserId($user->id);

        if (! $profile) {
            throw new HttpException(404, __('services::messages.service_provider.not_found'));
        }

        switch ($field) {

            case 'bio':
                $profile->update([
                    'bio' => null,
                ]);
                break;

            case 'cover':
                if ($profile->cover) {
                    FileManager::delete($profile->cover);
                }

                $profile->update([
                    'cover' => null,
                ]);
                break;

            case 'projects':
                $profile->projects()->delete();
                break;

            default:
                throw new HttpException(422, 'Invalid field.');
        }

        return $profile->fresh(['projects']);
    }
}
