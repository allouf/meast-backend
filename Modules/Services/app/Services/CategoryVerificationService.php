<?php

namespace Modules\Services\Services;

use Modules\Services\Enums\ProviderVerificationStatus;
use Modules\Services\Exceptions\CategoryVerificationRequiredException;
use Modules\Services\Models\ServiceCategory;
use Modules\Services\Models\ServiceProvider;

class CategoryVerificationService
{
    public function categoryRequiresVerification(int $categoryId): bool
    {
        $category = ServiceCategory::query()->find($categoryId);

        return $category !== null && (bool) $category->requires_verification;
    }

    public function providerIsApproved(ServiceProvider $provider): bool
    {
        $status = $provider->verification_status;

        if ($status instanceof ProviderVerificationStatus) {
            return $status->isApproved();
        }

        return (string) $status === ProviderVerificationStatus::Approved->value;
    }

    /**
     * Block publishing (active offers) in categories that require team verification.
     *
     * @throws CategoryVerificationRequiredException
     */
    public function assertCanPublishInCategory(ServiceProvider $provider, int $categoryId, bool $willBeActive): void
    {
        if (! $willBeActive) {
            return;
        }

        if (! $this->categoryRequiresVerification($categoryId)) {
            return;
        }

        if ($this->providerIsApproved($provider)) {
            return;
        }

        throw new CategoryVerificationRequiredException();
    }
}
