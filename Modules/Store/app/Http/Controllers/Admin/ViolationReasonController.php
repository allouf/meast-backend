<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\ViolationReasonResource;
use Modules\Store\Services\ViolationReasonService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class ViolationReasonController extends Controller
{
    public function __construct(
        protected ViolationReasonService $violationReasonService
    ) {}

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['is_active', 'search']);
            $reasons = $this->violationReasonService->getAllReasons($filters);

            return $this->successResponse(
                'Violation reasons retrieved successfully',
                ViolationReasonResource::collection($reasons)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse(
                $e,
                'An error occurred while retrieving violation reasons.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get a single reason by ID
     */
    public function show(int $id)
    {
        try {
            $reason = $this->violationReasonService->getReasonById($id);

            return $this->successResponse(
                'Violation reason retrieved successfully',
                new ViolationReasonResource($reason)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse(
                $e,
                'An error occurred while retrieving the violation reason.',
                Response::HTTP_NOT_FOUND
            );
        }
    }

    /**
     * Create a new violation reason
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $reason = $this->violationReasonService->createReason($request->only(['title', 'is_active']));

            return $this->successResponse(
                'Violation reason created successfully',
                new ViolationReasonResource($reason),
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            return $this->exceptionResponse(
                $e,
                'An error occurred while creating the violation reason.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update an existing reason
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $reason = $this->violationReasonService->updateReason($id, $request->only(['title', 'is_active']));

            return $this->successResponse(
                'Violation reason updated successfully',
                new ViolationReasonResource($reason)
            );
        } catch (Exception $e) {
            return $this->exceptionResponse(
                $e,
                'An error occurred while updating the violation reason.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete a reason
     */
    public function destroy(int $id)
    {
        try {
            $this->violationReasonService->deleteReason($id);

            return $this->successResponse(
                'Violation reason deleted successfully'
            );
        } catch (Exception $e) {
            return $this->exceptionResponse(
                $e,
                'An error occurred while deleting the violation reason.',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
