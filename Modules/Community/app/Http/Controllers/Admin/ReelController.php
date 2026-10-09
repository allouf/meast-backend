<?php

namespace Modules\Community\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\Community\Http\Requests\Admin\ReelRequest;
use Modules\Community\Http\Resources\Admin\ReelResource;
use Modules\Community\Services\ReelService;
use Exception;
use Illuminate\Http\Request;

class ReelController extends Controller
{
    protected $reelService;

    public function __construct(ReelService $reelService)
    {
        $this->reelService = $reelService;
    }

    /**
     * Get all reels with filters
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        $reels = $this->reelService->filter($filters);

        return $this->successResponse(
            __('messages.reels.retrieved_successfully'),
            ReelResource::collection($reels)
        );
    }

    /**
     * Get reel by ID
     */
    public function show($id)
    {
        $reel = $this->reelService->getReelById($id);

        if (!$reel) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.reels.retrieved_successfully'),
            new ReelResource($reel)
        );
    }

    /**
     * Update a reel
     */
    public function update(ReelRequest $request, $id)
    {
        $reel = $this->reelService->updateReel($id, $request->validated());

        if (!$reel) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.reels.updated_successfully'),
            new ReelResource($reel)
        );
    }

    /**
     * Delete a reel
     */
    public function destroy($id)
    {
        $deleted = $this->reelService->deleteReel($id);

        if (!$deleted) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.reels.deleted_successfully'),
            null
        );
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($id)
    {
        $reel = $this->reelService->getReelById($id);

        if (!$reel) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        $reel = $this->reelService->updateReel($id, [
            'is_featured' => !$reel->is_featured
        ]);

        return $this->successResponse(
            $reel->is_featured ? __('messages.reels.featured_successfully') : __('messages.reels.unfeatured_successfully'),
            new ReelResource($reel)
        );
    }

    /**
     * Change status
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,draft'
        ]);

        $reel = $this->reelService->updateReel($id, [
            'status' => $request->status
        ]);

        if (!$reel) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.reels.status_updated_successfully'),
            new ReelResource($reel)
        );
    }
}
