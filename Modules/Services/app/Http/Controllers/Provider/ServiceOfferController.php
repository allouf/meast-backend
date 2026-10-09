<?php

namespace Modules\Services\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Http\Requests\Provider\StoreServiceOfferRequest;
use Modules\Services\Http\Requests\Provider\UpdateServiceOfferRequest;
use Modules\Services\Http\Resources\Provider\ServiceOfferResource;
use Modules\Services\Exceptions\CategoryVerificationRequiredException;
use Modules\Services\Services\ServiceOfferService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceOfferController extends Controller
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService,
        protected ServiceOfferService $serviceOfferService
    ) {}

    protected function getProfile()
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            throw new HttpException(401, __('services::messages.service_provider.not_found'));
        }
        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            throw new NotFoundHttpException(__('services::messages.service_provider.not_found'));
        }
        return $profile;
    }

    /**
     * Get service offer counts per status for the current provider (dashboard stats).
     */
    public function stats()
    {
        try {
            $profile = $this->getProfile();
            $data = $this->serviceOfferService->getStatsByProvider($profile);
            return $this->successResponse(
                __('services::messages.service_offers.retrieved_successfully'),
                $data
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * List my service offers (paginated).
     */
    public function index(Request $request)
    {
        try {
            $profile = $this->getProfile();
            $filters = $request->only([
                'per_page',
                'page',
                'is_active',
                'service_category_id',
                'category_id',
                'delivery_model',
                'service_date_from',
                'service_date_to',
                'service_type',
                'type',
                'status',
                'search',
                'sort',
            ]);
            $result = $this->serviceOfferService->getByProvider($profile, $filters);
            return $this->successResponse(
                __('services::messages.service_offers.retrieved_successfully'),
                [
                    'offers' => ServiceOfferResource::collection($result->items()),
                    'meta' => [
                        'current_page' => $result->currentPage(),
                        'last_page' => $result->lastPage(),
                        'per_page' => $result->perPage(),
                        'total' => $result->total(),
                    ],
                ]
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Show one service offer.
     */
    public function show(int $id)
    {
        try {
            $profile = $this->getProfile();
            $offer = $this->serviceOfferService->getById($id, $profile->id);
            if (! $offer) {
                return $this->errorResponse(__('services::messages.service_offers.not_found'), 404);
            }
            return $this->successResponse(
                __('services::messages.service_offers.retrieved_successfully'),
                new ServiceOfferResource($offer)
            );
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Create service offer.
     */
    public function store(StoreServiceOfferRequest $request)
    {
        try {
            $profile = $this->getProfile();
            $media = $request->input('media', []);

            foreach ($media as $index => $item) {
                $file = $request->file("media.$index.file");

                if (!$file) {
                    continue;
                }

                $media[$index]['file'] = $file;
            }

            $offer = $this->serviceOfferService->create(
                $profile,
                $request->validated(),
                [
                    'cover' => $request->file('cover'),
                    'media' => $media
                ]
            );
            return $this->successResponse(
                __('services::messages.service_offers.created_successfully'),
                new ServiceOfferResource($offer->load('serviceCategory')),
                201
            );
        } catch (CategoryVerificationRequiredException $e) {
            return $this->verificationRequiredResponse($e);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Update service offer.
     */
    public function update(UpdateServiceOfferRequest $request, int $id)
    {
        try {
            $profile = $this->getProfile();
            $offer = $this->serviceOfferService->getById($id, $profile->id);
            if (! $offer) {
                return $this->errorResponse(__('services::messages.service_offers.not_found'), 404);
            }

            $mediaInput = $request->input('media');
            $media = [];

            if (is_array($mediaInput)) {
                foreach ($mediaInput as $index => $item) {

                    $file = $request->file("media.$index.file");

                    $media[] = [
                        'id' => $item['id'] ?? null,
                        'file' => $file,
                        'sort_order' => $item['sort_order'] ?? null,
                    ];
                }
            }

            $files = [
                'cover' => $request->file('cover'),
                'media' => is_array($mediaInput) ? $media : null
            ];

            $offer = $this->serviceOfferService->update(
                $offer,
                $request->validated(),
                $files
            );
            return $this->successResponse(
                __('services::messages.service_offers.updated_successfully'),
                new ServiceOfferResource($offer)
            );
        } catch (CategoryVerificationRequiredException $e) {
            return $this->verificationRequiredResponse($e);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    /**
     * Delete service offer.
     */
    public function destroy(int $id)
    {
        try {
            $profile = $this->getProfile();
            $offer = $this->serviceOfferService->getById($id, $profile->id);
            if (! $offer) {
                return $this->errorResponse(__('services::messages.service_offers.not_found'), 404);
            }
            $this->serviceOfferService->delete($offer);
            return $this->successResponse(__('services::messages.service_offers.deleted_successfully'), null);
        } catch (NotFoundHttpException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

    protected function verificationRequiredResponse(CategoryVerificationRequiredException $e)
    {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage(),
            'data' => [
                'code' => CategoryVerificationRequiredException::ERROR_CODE,
            ],
        ], Response::HTTP_FORBIDDEN);
    }
}
