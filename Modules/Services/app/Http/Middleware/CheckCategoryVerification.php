<?php

namespace Modules\Services\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Services\Exceptions\CategoryVerificationRequiredException;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Services\CategoryVerificationService;
use Modules\Services\Services\ServiceProviderService;
use Symfony\Component\HttpFoundation\Response;

class CheckCategoryVerification
{
    public function __construct(
        protected ServiceProviderService $serviceProviderService,
        protected CategoryVerificationService $categoryVerificationService,
    ) {}

    /**
     * Block service offer create/update when publishing in a category that requires verification
     * and the provider is not approved.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('api')->user();
        if (! $user) {
            return $next($request);
        }

        $profile = $this->serviceProviderService->getByUser($user);
        if (! $profile) {
            return $next($request);
        }

        try {
            [$categoryId, $willBeActive] = $this->resolveCategoryAndActiveState($request, $profile);

            if ($categoryId === null) {
                return $next($request);
            }

            $this->categoryVerificationService->assertCanPublishInCategory(
                $profile,
                $categoryId,
                $willBeActive
            );
        } catch (CategoryVerificationRequiredException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'code' => CategoryVerificationRequiredException::ERROR_CODE,
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    /**
     * @return array{0: int|null, 1: bool}
     */
    private function resolveCategoryAndActiveState(Request $request, $profile): array
    {
        $offerId = $request->route('id');

        if ($offerId !== null) {
            $offer = ServiceOffer::query()
                ->where('id', $offerId)
                ->where('service_provider_id', $profile->id)
                ->first();

            if (! $offer) {
                return [null, false];
            }

            $categoryId = (int) ($request->input('service_category_id') ?? $offer->service_category_id);
            $willBeActive = $request->has('is_active')
                ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
                : (bool) $offer->is_active;

            return [$categoryId, $willBeActive];
        }

        if (! $request->has('service_category_id')) {
            return [null, false];
        }

        $categoryId = (int) $request->input('service_category_id');
        $willBeActive = $request->has('is_active')
            ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            : true;

        return [$categoryId, $willBeActive];
    }
}
