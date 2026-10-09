<?php

namespace Modules\Services\Services;

use App\Utilities\FileManager;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Services\Enums\CustomRequestStatus;
use Modules\Services\Enums\QuoteStatus;
use Modules\Services\Models\CustomRequest;
use Modules\Services\Models\Order;
use Modules\Services\Models\Quote;
use Modules\Services\Repositories\Interfaces\CustomRequestAttachmentRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestCategoryRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestOrderRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestQuoteRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomRequestService
{
    public function __construct(
        protected CustomRequestCategoryRepositoryInterface $customRequestCategoryRepository,
        protected CustomRequestRepositoryInterface $customRequestRepository,
        protected CustomRequestQuoteRepositoryInterface $customRequestQuoteRepository,
        protected CustomRequestAttachmentRepositoryInterface $customRequestAttachmentRepository,
        protected CustomRequestOrderRepositoryInterface $customRequestOrderRepository,
    ) {}

    public function listCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->customRequestCategoryRepository->allOrderedByName();
    }

    public function listForClient(int $clientId, ?CustomRequestStatus $status, int $perPage): LengthAwarePaginator
    {
        return $this->customRequestRepository->paginateForClient($clientId, $status, $perPage);
    }

    public function createForClient(int $clientId, array $data, array $files = []): CustomRequest
    {
        return DB::transaction(function () use ($clientId, $data, $files) {
            $rfq = $this->customRequestRepository->create([
                'client_id' => $clientId,
                'category_id' => $data['category_id'],
                'reference_number' => $this->generateUniqueReferenceNumber(),
                'title' => $data['title'],
                'description' => $data['description'],
                'delivery_model' => $data['delivery_model'],
                'deadline_date' => $data['deadline_date'],
                'budget_min' => $data['budget_min'] ?? null,
                'budget_max' => $data['budget_max'] ?? null,
                'status' => CustomRequestStatus::Open,
            ]);

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $path = FileManager::upload('custom-requests/attachments', $file);
                    $this->customRequestAttachmentRepository->createForCustomRequest($rfq, [
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType() ?? $file->getMimeType() ?? 'application/octet-stream',
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $rfq->fresh(['category', 'attachments']);
        });
    }

    public function updateForClient(int $clientId, int $rfqId, array $data, array $files = []): CustomRequest
    {
        return DB::transaction(function () use ($clientId, $rfqId, $data, $files) {

            $rfq = $this->customRequestRepository
                ->findWithDetailsForClient($clientId, $rfqId);

            if ($rfq->status !== CustomRequestStatus::Open) {
                throw new HttpException(422, 'Only open requests can be updated');
            }

            $updateData = array_filter($data, fn($value) => $value !== null);

            $rfq = $this->customRequestRepository->update($rfq, $updateData);

            foreach ($files as $file) {
                if ($file instanceof UploadedFile) {
                    $path = FileManager::upload('custom-requests/attachments', $file);

                    $this->customRequestAttachmentRepository->createForCustomRequest($rfq, [
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType() ?? 'application/octet-stream',
                        'file_size' => $file->getSize(),
                    ]);
                }
            }

            return $rfq->fresh(['category', 'attachments']);
        });
    }

    public function getDetailsForClient(int $clientId, int $rfqId): CustomRequest
    {
        return $this->customRequestRepository->findWithDetailsForClient($clientId, $rfqId);
    }

    public function cancelForClient(int $clientId, int $rfqId): CustomRequest
    {
        return DB::transaction(function () use ($clientId, $rfqId) {
            $rfq = $this->customRequestRepository->lockForUpdateForClientOrFail($clientId, $rfqId);

            if ($rfq->status !== CustomRequestStatus::Open) {
                throw new HttpException(422, __('services::messages.custom_request.only_open_may_be_cancelled'));
            }

            if ($this->customRequestQuoteRepository->rfqHasAcceptedQuote($rfq->id)) {
                throw new HttpException(422, __('services::messages.custom_request.cannot_cancel_with_accepted_quote'));
            }

            return $this->customRequestRepository->update($rfq, [
                'status' => CustomRequestStatus::Canceled,
            ], ['category', 'attachments']);
        });
    }

    public function listQuotesForClient(int $clientId, int $rfqId): CustomRequest
    {
        return $this->customRequestRepository->findWithQuotesForClient($clientId, $rfqId);
    }

    public function acceptQuoteForClient(int $clientId, int $quoteId): Order
    {
        return DB::transaction(function () use ($clientId, $quoteId) {
            $quote = $this->customRequestQuoteRepository->findForUpdateOrFail($quoteId);

            $rfq = $this->customRequestRepository->lockForUpdateByIdOrFail($quote->rfq_id);

            if ((int) $rfq->client_id !== $clientId) {
                throw new HttpException(403, __('services::messages.custom_request.forbidden'));
            }

            if ($rfq->status !== CustomRequestStatus::Open) {
                throw new HttpException(422, __('services::messages.custom_request.not_open_for_award'));
            }

            if ($quote->status !== QuoteStatus::Pending) {
                throw new HttpException(422, __('services::messages.custom_request.quote_not_pending'));
            }

            if ($this->customRequestQuoteRepository->orderExistsForQuote($quote->id)) {
                throw new HttpException(422, __('services::messages.custom_request.order_already_exists'));
            }

            $this->customRequestQuoteRepository->rejectPendingQuotesExcept($rfq->id, $quote->id);

            $servicePrice = $quote->proposed_budget;

            $this->customRequestQuoteRepository->update($quote, ['status' => QuoteStatus::Accepted]);
            $this->customRequestRepository->update($rfq, ['status' => CustomRequestStatus::Awarded]);
            $vatAmount = 0;
            $feesAmount = 0;
            $totalAmount = $servicePrice + $vatAmount + $feesAmount;

            return $this->customRequestOrderRepository->create([
                'quote_id' => $quote->id,
                'client_id' => $rfq->client_id,
                'provider_id' => $quote->provider_id,
                'service_price' => $servicePrice,
                'vat_amount' => $vatAmount,
                'fees_amount' => $feesAmount,
                'total_amount' => $totalAmount,
                'payment_method' => 'pending',
            ]);
        });
    }

    public function discoverRequestsForProvider(int $userId, int $serviceProviderId, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->customRequestRepository->paginateDiscoverForProvider($userId, $serviceProviderId, $filters, $perPage);
    }

    public function discoverRequestShowForProvider(int $userId, int $serviceProviderId, int $rfqId): CustomRequest
    {
        $rfq = $this->customRequestRepository->findByIdWithDiscoverRelations($rfqId, $serviceProviderId);

        if ((int) $rfq->client_id === $userId) {
            throw new HttpException(403, __('services::messages.custom_request.discover_own_forbidden'));
        }

        // if ($rfq->status !== CustomRequestStatus::Open) {
        //     throw new HttpException(404, __('services::messages.custom_request.discover_not_open'));
        // }

        return $rfq;
    }

    public function providerQuotesStats(int $serviceProviderId): array
    {
        return $this->customRequestQuoteRepository->getDashboardStatsForProvider($serviceProviderId);
    }

    public function providerQuotesList(int $serviceProviderId, int $perPage): LengthAwarePaginator
    {
        return $this->customRequestQuoteRepository->paginateForProviderWithCustomRequest($serviceProviderId, $perPage);
    }

    public function updateProviderQuote(int $serviceProviderId, int $quoteId, array $data): Quote
    {
        return DB::transaction(function () use ($serviceProviderId, $quoteId, $data) {
            $quote = $this->customRequestQuoteRepository->lockForProviderOrFail($quoteId, $serviceProviderId);

            if ($quote->status !== QuoteStatus::Pending) {
                throw new HttpException(422, __('services::messages.custom_request_quote.only_pending_may_be_edited'));
            }

            return $this->customRequestQuoteRepository->update($quote, [
                'proposed_budget' => $data['proposed_budget'],
                'cover_letter_or_message' => $data['cover_letter_or_message'],
            ]);
        });
    }

    public function cancelProviderQuote(int $serviceProviderId, int $quoteId): Quote
    {
        return DB::transaction(function () use ($serviceProviderId, $quoteId) {
            $quote = $this->customRequestQuoteRepository->lockForProviderOrFail($quoteId, $serviceProviderId);

            if ($quote->status !== QuoteStatus::Pending) {
                throw new HttpException(422, __('services::messages.custom_request_quote.only_pending_may_be_canceled'));
            }

            return $this->customRequestQuoteRepository->update($quote, [
                'status' => QuoteStatus::Canceled,
            ]);
        });
    }

    public function submitQuoteForProvider(int $userId, int $serviceProviderId, CustomRequest $customRequest, array $data): Quote
    {
        return DB::transaction(function () use ($userId, $serviceProviderId, $customRequest, $data) {
            $rfq = $this->customRequestRepository->lockForUpdateByIdOrFail($customRequest->getKey());

            if ((int) $rfq->client_id === $userId) {
                throw new HttpException(422, __('services::messages.custom_request.cannot_quote_own_request'));
            }

            if ($rfq->status !== CustomRequestStatus::Open) {
                throw new HttpException(422, __('services::messages.custom_request.not_accepting_quotes'));
            }

            if ($this->customRequestQuoteRepository->rfqHasProviderQuote($rfq->id, $serviceProviderId)) {
                throw new HttpException(422, __('services::messages.custom_request.duplicate_quote'));
            }

            $quote = $this->customRequestQuoteRepository->createForCustomRequest($rfq, [
                'provider_id' => $serviceProviderId,
                'proposed_budget' => $data['proposed_budget'],
                'estimated_delivery_date' => $data['estimated_delivery_date'],
                'cover_letter_or_message' => $data['cover_letter_or_message'],
                'status' => QuoteStatus::Pending,
            ]);

            return $quote->load(['provider.user:id,name,photo,country_code']);
        });
    }

    protected function generateUniqueReferenceNumber(): string
    {
        do {
            $ref = '#RFQ-' . random_int(10000, 999999);
        } while ($this->customRequestRepository->referenceNumberExists($ref));

        return $ref;
    }
}
