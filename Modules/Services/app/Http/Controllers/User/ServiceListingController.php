<?php

namespace Modules\Services\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\User\ReportServiceOfferRequest;
use Modules\Services\Http\Requests\User\SubmitServiceOfferReviewRequest;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Http\Resources\User\MilestonePackageDetailResource;
use Modules\Services\Http\Resources\User\MilestonePackageListResource;
use Modules\Services\Http\Resources\User\ServiceOfferDetailResource;
use Modules\Services\Http\Resources\User\ServiceOfferListResource;
use Modules\Services\Http\Resources\User\ProviderProjectResource;
use Modules\Services\Repositories\Interfaces\MilestoneRepositoryInterface;
use Modules\Services\Services\ServiceOfferReportService;
use Modules\Services\Services\ServiceOfferReviewService;
use Modules\Services\Services\ServiceOfferService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceListingController extends Controller
{
    public function __construct(
        protected ServiceOfferService $serviceOfferService,
        protected MilestoneRepositoryInterface $milestoneRepository,
        protected ServiceOfferReportService $serviceOfferReportService,
        protected ServiceOfferReviewService $serviceOfferReviewService
    ) {}

    /**
     * List services for browse page (filters + sort).
     * GET api/v1/services?orderable_type=service_offer|milestone_template&category_id=&delivery_model=&type=&...
     * orderable_type: service_offer (default) | milestone_template
     * type: service offer pricing type (fixed_price, etc.) — only for service_offer listings
     * sort: newest | oldest | lowest_price | highest_price | lowest_rate | highest_rate
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'category_id',
            'service_category_id',
            'delivery_model',
            'type',
            'service_type',
            'min_price',
            'max_price',
            'min_rating',
            'search',
            'sort',
            'page',
            'per_page',
            'viewer_country_code',
        ]);

        $orderableType = OrderableType::tryFrom(
            (string) $request->query('orderable_type', OrderableType::ServiceOffer->value)
        ) ?? OrderableType::ServiceOffer;

        if ($orderableType === OrderableType::MilestoneTemplate) {
            $paginator = $this->milestoneRepository->getPublicActiveTemplates($filters);

            return $this->successResponse(
                __('services::messages.service_offers.list_retrieved_successfully'),
                [
                    'orderable_type' => OrderableType::MilestoneTemplate->value,
                    'services' => MilestonePackageListResource::collection($paginator->items()),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ],
                ]
            );
        }

        $result = $this->serviceOfferService->getListForPublic($filters);
        $paginator = $result['paginator'];

        return $this->successResponse(
            __('services::messages.service_offers.list_retrieved_successfully'),
            [
                'orderable_type' => OrderableType::ServiceOffer->value,
                'services' => ServiceOfferListResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'ranking_snapshot_at' => $result['ranking_snapshot_at'],
                    'ranking_pool_limited' => $result['ranking_pool_limited'],
                ],
            ]
        );
    }

    /**
     * Show a single service or milestone package (public).
     * GET api/v1/services/show/{id}?orderable_type=service_offer|milestone_template
     */
    public function show(Request $request, int $id)
    {
        $orderableType = OrderableType::tryFrom(
            (string) $request->query('orderable_type', OrderableType::ServiceOffer->value)
        ) ?? OrderableType::ServiceOffer;

        if ($orderableType === OrderableType::MilestoneTemplate) {
            $template = $this->milestoneRepository->findPublicTemplateById($id);
            if (! $template) {
                throw new NotFoundHttpException(__('services::messages.milestones.not_found'));
            }

            return $this->successResponse(
                __('services::messages.milestones.retrieved_successfully'),
                new MilestonePackageDetailResource($template)
            );
        }

        $offer = $this->serviceOfferService->getPublicById($id);
        if (! $offer) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }

        return $this->successResponse(
            __('services::messages.service_offers.retrieved_successfully'),
            new ServiceOfferDetailResource($offer)
        );
    }

    /**
     * Get projects (portfolio) linked to a service offer (paginated).
     * GET api/v1/services/{serviceId}/projects?page=1&per_page=15
     */
    public function projects(Request $request, int $serviceId)
    {
        $paginator = $this->serviceOfferService->getProjectsByServiceId($serviceId, $request->only([
            'page',
            'per_page',
            'category_id',
            'date_range',
            'search'
        ]));
        if ($paginator === null) {
            throw new NotFoundHttpException(__('services::messages.service_offers.not_found'));
        }
        return $this->successResponse(
            __('services::messages.provider_project.retrieved_successfully'),
            [
                'projects' => ProviderProjectResource::collection($paginator->items()),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]
        );
    }

    /**
     * Report a service (authenticated user).
     * POST api/v1/services/{serviceId}/report
     * Body: { "reason": "spam|inappropriate_content|misleading|scam|other", "description": "optional, max 500" }
     */
    public function report(ReportServiceOfferRequest $request, int $serviceId)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            throw new HttpException(401, __('services::messages.service_offers.report_unauthorized'));
        }
        $this->serviceOfferReportService->submitReport(
            (int) $user->id,
            $serviceId,
            $request->validated('reason'),
            $request->validated('description')
        );
        return $this->successResponse(__('services::messages.service_offers.report_submitted_successfully'), null);
    }

    /**
     * Submit or update a review for a service (authenticated user).
     * POST api/v1/services/{serviceId}/review
     * Body: { "rating": 1-5, "description": "optional, max 500" }
     */
    public function review(SubmitServiceOfferReviewRequest $request, int $serviceId)
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            throw new HttpException(401, __('services::messages.service_offers.report_unauthorized'));
        }
        $review = $this->serviceOfferReviewService->submitReview(
            (int) $user->id,
            $serviceId,
            (int) $request->validated('rating'),
            $request->validated('description')
        );
        return $this->successResponse(
            __('services::messages.service_offers.review_submitted_successfully'),
            [
                'id' => $review->id,
                'rating' => $review->rating,
                'description' => $review->description,
                'created_at' => $review->created_at?->toDateTimeString(),
            ]
        );
    }
}
