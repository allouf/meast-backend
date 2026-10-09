<?php

namespace Modules\Store\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Store\Services\StoreMemberPermissionService;
use Symfony\Component\HttpFoundation\Response;

class StoreMemberPermission
{
    protected $permissionService;

    public function __construct(StoreMemberPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, string $storeIdParam = 'storeId'): Response
    {
        try {
            $storeId = $request->route($storeIdParam) ?? $request->input('store_id');

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Store ID is required',
                ], 400);
            }

            // Check if user has the required permission
            if (!$this->permissionService->hasPermission($storeId, $permission)) {
                return response()->json([
                    'success' => false,
                    'message' => "You do not have permission to perform this action: {$permission}",
                ], 403);
            }

            return $next($request);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
