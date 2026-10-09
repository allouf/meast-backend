<?php

namespace Modules\Community\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Community\Http\Requests\User\CommentRequest;
use Modules\Community\Http\Requests\User\ReelRequest;
use Modules\Community\Http\Resources\User\CommentResource;
use Modules\Community\Http\Resources\User\ReactionResource;
use Modules\Community\Http\Resources\User\ReelResource;
use Modules\Community\Services\CommentService;
use Modules\Community\Services\ReactionService;
use Modules\Community\Services\ReelService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReelController extends Controller
{
    protected $reelService;
    protected $commentService;
    protected $reactionService;

    public function __construct(
        ReelService $reelService,
        CommentService $commentService,
        ReactionService $reactionService
    ) {
        $this->reelService = $reelService;
        $this->commentService = $commentService;
        $this->reactionService = $reactionService;
    }

    /**
     * Get all reels
     */
    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'search', 'user_id']);
        $reels = $this->reelService->getAllReels($filters);

        // Check if reels is already paginated (has 'data' key)
        if (is_array($reels) && isset($reels['data'])) {
            return $this->successResponse(
                __('messages.reels.retrieved_successfully'),
                [
                    'data' => ReelResource::collection($reels['data']),
                    'meta' => $reels['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.reels.retrieved_successfully'),
            ReelResource::collection($reels)
        );
    }

    /**
     * Get featured reels
     */
    public function featured()
    {
        $reels = $this->reelService->getFeaturedReels();

        return $this->successResponse(
            __('messages.reels.featured_retrieved_successfully'),
            ReelResource::collection($reels)
        );
    }

    /**
     * Get reel by ID with details
     */
    public function show($id)
    {
        $reel = $this->reelService->getReelWithDetails($id);

        if (!$reel) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.reels.retrieved_successfully'),
            new ReelResource($reel)
        );
    }

    /**
     * Create a new reel
     */
    public function store(ReelRequest $request)
    {
        $data = $request->validated();
        if (!isset($data['country_code']) && Auth::user()->country_code) {
            $data['country_code'] = Auth::user()->country_code;
        }
        if (!isset($data['region']) && Auth::user()->region) {
            $data['region'] = Auth::user()->region;
        }
        try {
            $reel = $this->reelService->createReel($data);
        } catch (Exception $e) {
            return $this->exceptionResponse($e);
        }

        return $this->successResponse(
            __('messages.reels.created_successfully'),
            new ReelResource($reel),
            201
        );
    }

    /**
     * Update a reel
     */
    public function update(ReelRequest $request, $id)
    {
        $reel = $this->reelService->updateReel($id, $request->validated());

        if (!$reel) {
            return $this->errorResponse(__('messages.reels.not_found_or_unauthorized'), 404);
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
            return $this->errorResponse(__('messages.reels.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.reels.deleted_successfully'),
            null
        );
    }

    /**
     * Get my reels
     */
    public function myReels(Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $reels = $this->reelService->getMyReels($filters);

        // Check if reels is already paginated (has 'data' key)
        if (is_array($reels) && isset($reels['data'])) {
            return $this->successResponse(
                __('messages.reels.my_reels_retrieved_successfully'),
                [
                    'data' => ReelResource::collection($reels['data']),
                    'meta' => $reels['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.reels.my_reels_retrieved_successfully'),
            ReelResource::collection($reels)
        );
    }

    /**
     * Get user reels
     */
    public function userReels($userId, Request $request)
    {
        $filters = $request->only(['per_page', 'status']);
        $reels = $this->reelService->getUserReels($userId, $filters);

        // Check if reels is already paginated (has 'data' key)
        if (is_array($reels) && isset($reels['data'])) {
            return $this->successResponse(
                __('messages.reels.user_reels_retrieved_successfully'),
                [
                    'data' => ReelResource::collection($reels['data']),
                    'meta' => $reels['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.reels.user_reels_retrieved_successfully'),
            ReelResource::collection($reels)
        );
    }

    /**
     * Search reels
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $filters = $request->only(['per_page', 'user_id']);

        if (!$query) {
            return $this->errorResponse(__('messages.reels.search_query_required'), 400);
        }

        $reels = $this->reelService->searchReels($query, $filters);

        // Check if reels is already paginated (has 'data' key)
        if (is_array($reels) && isset($reels['data'])) {
            return $this->successResponse(
                __('messages.reels.search_results_retrieved_successfully'),
                [
                    'data' => ReelResource::collection($reels['data']),
                    'meta' => $reels['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.reels.search_results_retrieved_successfully'),
            ReelResource::collection($reels)
        );
    }

    /**
     * Toggle reaction on reel
     */
    public function toggleReaction(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);

        $reaction = $this->reelService->toggleReaction($id, $request->type);

        return $this->successResponse(
            $reaction ? __('messages.reactions.toggled_successfully') : __('messages.reactions.removed_successfully'),
            $reaction ? new ReactionResource($reaction) : null
        );
    }

    /**
     * Get reel reactions
     */
    public function reactions($id, Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->reelService->getReelReactions($id, $filters);

        // Check if reactions is already paginated (has 'data' key)
        if (is_array($reactions) && isset($reactions['data'])) {
            return $this->successResponse(
                __('messages.reactions.retrieved_successfully'),
                [
                    'data' => ReactionResource::collection($reactions['data']),
                    'meta' => $reactions['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.reactions.retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get reel comments
     */
    public function comments($id, Request $request)
    {
        $filters = $request->only(['per_page']);
        $comments = $this->reelService->getReelComments($id, $filters);

        // Check if comments is already paginated (has 'data' key)
        if (is_array($comments) && isset($comments['data'])) {
            return $this->successResponse(
                __('messages.comments.retrieved_successfully'),
                [
                    'data' => CommentResource::collection($comments['data']),
                    'meta' => $comments['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.comments.retrieved_successfully'),
            CommentResource::collection($comments)
        );
    }

    /**
     * Get reel top-level comments
     */
    public function topLevelComments($id)
    {
        $comments = $this->reelService->getReelTopLevelComments($id);

        // Check if comments is already paginated (has 'data' key)
        if (is_array($comments) && isset($comments['data'])) {
            return $this->successResponse(
                __('messages.comments.top_level_retrieved_successfully'),
                [
                    'data' => CommentResource::collection($comments['data']),
                    'meta' => $comments['meta']
                ]
            );
        }

        return $this->successResponse(
            __('messages.comments.top_level_retrieved_successfully'),
            CommentResource::collection($comments)
        );
    }

    /**
     * Add comment to reel
     */
    public function addComment(CommentRequest $request, $id)
    {
        $comment = $this->reelService->addComment($id, $request->validated());

        if (!$comment) {
            return $this->errorResponse(__('messages.reels.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.comments.created_successfully'),
            new CommentResource($comment),
            201
        );
    }
}
