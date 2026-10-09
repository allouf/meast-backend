<?php

namespace Modules\Community\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Traits\BaseResponse;
use Modules\Community\Http\Resources\User\ReactionResource;
use Modules\Community\Services\ReactionService;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    use BaseResponse;

    protected $reactionService;

    public function __construct(ReactionService $reactionService)
    {
        $this->reactionService = $reactionService;
    }

    /**
     * Get all reactions
     */
    public function index(Request $request)
    {
        $filters = $request->only(['per_page', 'user_id', 'post_id', 'type']);
        $reactions = $this->reactionService->getAllReactions($filters);

        return $this->successResponse(
            __('messages.reactions.retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get reaction by ID
     */
    public function show($id)
    {
        $reaction = $this->reactionService->getReactionById($id);

        if (!$reaction) {
            return $this->errorResponse(__('messages.reactions.not_found'), 404);
        }

        return $this->successResponse(
            __('messages.reactions.retrieved_successfully'),
            new ReactionResource($reaction)
        );
    }

    /**
     * Create a new reaction
     */
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);

        $reaction = $this->reactionService->createReaction($request->only(['post_id', 'type']));

        return $this->successResponse(
            __('messages.reactions.created_successfully'),
            new ReactionResource($reaction),
            201
        );
    }

    /**
     * Update a reaction
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);

        $reaction = $this->reactionService->updateReaction($id, $request->only(['type']));

        if (!$reaction) {
            return $this->errorResponse(__('messages.reactions.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.reactions.updated_successfully'),
            new ReactionResource($reaction)
        );
    }

    /**
     * Delete a reaction
     */
    public function destroy($id)
    {
        $deleted = $this->reactionService->deleteReaction($id);

        if (!$deleted) {
            return $this->errorResponse(__('messages.reactions.not_found_or_unauthorized'), 404);
        }

        return $this->successResponse(
            __('messages.reactions.deleted_successfully'),
            null
        );
    }

    /**
     * Get my reactions
     */
    public function myReactions(Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->reactionService->getMyReactions($filters);

        return $this->successResponse(
            __('messages.reactions.my_reactions_retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get user reactions
     */
    public function userReactions($userId, Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->reactionService->getUserReactions($userId, $filters);

        return $this->successResponse(
            __('messages.reactions.user_reactions_retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get post reactions
     */
    public function postReactions($postId, Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->reactionService->getPostReactions($postId, $filters);

        return $this->successResponse(
            __('messages.reactions.post_reactions_retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get reel reactions
     */
    public function reelReactions($reelId, Request $request)
    {
        $filters = $request->only(['per_page', 'type']);
        $reactions = $this->reactionService->getReelReactions($reelId, $filters);

        return $this->successResponse(
            __('messages.reactions.reel_reactions_retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Get reactions by type for a post
     */
    public function reactionsByType($postId, $type)
    {
        $reactions = $this->reactionService->getReactionsByType($postId, $type);

        return $this->successResponse(
            __('messages.reactions.by_type_retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Toggle reaction on post
     */
    public function toggle(Request $request, $postId)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);

        $reaction = $this->reactionService->toggleReaction($postId, $request->type);

        return $this->successResponse(
            $reaction ? __('messages.reactions.toggled_successfully') : __('messages.reactions.removed_successfully'),
            $reaction ? new ReactionResource($reaction) : null
        );
    }

    /**
     * Get user reaction for a post
     */
    public function userReaction($postId)
    {
        $reaction = $this->reactionService->getUserReaction($postId);

        return $this->successResponse(
            __('messages.reactions.user_reaction_retrieved_successfully'),
            $reaction ? new ReactionResource($reaction) : null
        );
    }

    /**
     * Get reactions count for a post
     */
    public function count($postId)
    {
        $count = $this->reactionService->getReactionsCount($postId);

        return $this->successResponse(
            __('messages.reactions.count_retrieved_successfully'),
            ['count' => $count]
        );
    }

    /**
     * Get reactions summary for a post
     */
    public function summary($postId)
    {
        $summary = $this->reactionService->getReactionsSummary($postId);

        return $this->successResponse(
            __('messages.reactions.summary_retrieved_successfully'),
            $summary
        );
    }

    /**
     * Get reactions by type for a reel
     */
    public function reactionsByTypeForReel($reelId, $type)
    {
        $reactions = $this->reactionService->getReactionsByTypeForReel($reelId, $type);

        return $this->successResponse(
            __('messages.reactions.by_type_retrieved_successfully'),
            ReactionResource::collection($reactions)
        );
    }

    /**
     * Toggle reaction on reel
     */
    public function toggleForReel(Request $request, $reelId)
    {
        $request->validate([
            'type' => 'required|in:like,love,haha,wow,sad,angry'
        ]);

        $reaction = $this->reactionService->toggleReactionForReel($reelId, $request->type);

        return $this->successResponse(
            $reaction ? __('messages.reactions.toggled_successfully') : __('messages.reactions.removed_successfully'),
            $reaction ? new ReactionResource($reaction) : null
        );
    }

    /**
     * Get user reaction for a reel
     */
    public function userReactionForReel($reelId)
    {
        $reaction = $this->reactionService->getUserReactionForReel($reelId);

        return $this->successResponse(
            __('messages.reactions.user_reaction_retrieved_successfully'),
            $reaction ? new ReactionResource($reaction) : null
        );
    }

    /**
     * Get reactions count for a reel
     */
    public function countForReel($reelId)
    {
        $count = $this->reactionService->getReactionsCountForReel($reelId);

        return $this->successResponse(
            __('messages.reactions.count_retrieved_successfully'),
            ['count' => $count]
        );
    }

    /**
     * Get reactions summary for a reel
     */
    public function summaryForReel($reelId)
    {
        $summary = $this->reactionService->getReactionsSummaryForReel($reelId);

        return $this->successResponse(
            __('messages.reactions.summary_retrieved_successfully'),
            $summary
        );
    }
}
