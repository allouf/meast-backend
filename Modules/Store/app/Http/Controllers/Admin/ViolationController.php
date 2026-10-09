<?php

namespace Modules\Store\Http\Controllers\Admin;

use Modules\Store\Enums\ViolationStatus;
use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\Admin\ViolationResource;
use Modules\Store\Services\ViolationService;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function __construct(
        protected ViolationService $violationService,
        // protected ViolationReasonService $violationReasonService
    ) {}

    public function index(Request $request)
    {
        $validated = $request->validate([
            'status'     => 'nullable|string',
            'severity'   => 'nullable|string',
            'reason_id'  => 'nullable|exists:violation_reasons,id',
            'type'       => 'nullable|string|in:store,product,post,comment',
            'search'     => 'nullable|string',
            'from_date'  => 'nullable|date',
            'to_date'    => 'nullable|date|after_or_equal:from_date',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $violations = $this->violationService->all($validated);

        return $this->successResponse(
            'Violations retrieved successfully',
            [
                'violations' => ViolationResource::collection($violations['data']),
                'meta' => $violations['meta']
            ]
        );
    }

    public function show($id)
    {
        try {
            // استدعاء الخدمة لجلب بيانات المخالفة
            $violation = $this->violationService->findById($id);

            // إعادة Resource
            return $this->successResponse(
                'Violation retrieved successfully',
                new ViolationResource($violation)
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Violation not found', 404);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e, 'An error occurred while retrieving the violation.');
        }
    }

    public function takeAction(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:reviewed,action_taken,ignored',
            'admin_note' => 'nullable|string|max:500',
        ]);

        try {
            $statusEnum = ViolationStatus::from($request->status);

            $violation = $this->violationService->takeAction(
                $id,
                $statusEnum,
                $request->admin_note
            );

            return $this->successResponse(
                'Violation updated successfully',
                new ViolationResource($violation)
            );

        } catch (\ValueError $e) {
            return $this->errorResponse('Invalid status value.', 422);
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->exceptionResponse($e);
        }
    }

}
